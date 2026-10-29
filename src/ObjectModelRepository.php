<?php

namespace Zalt\Model;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Zalt\Base\TranslatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\MetaModelException;
use Zalt\Model\Mapping\CallableRef;
use Zalt\Model\Mapping\Custom;
use Zalt\Model\Mapping\Database\Column;
use Zalt\Model\Mapping\Database\JoinTable;
use Zalt\Model\Mapping\Database\JoinType;
use Zalt\Model\Mapping\Database\Table;
use Zalt\Model\Mapping\MetaSetterInterface;
use Zalt\Model\Mapping\Trans;
use Zalt\Model\Mapping\Transformer;
use Zalt\Model\Ra\PhpArrayModel;
use Zalt\Model\Sql\JoinModel;
use Zalt\Model\Sql\SqlRunnerInterface;

class ObjectModelRepository
{
    public const PREFIX_DELIMITER = '_';

    public function __construct(
        private readonly MetaModelLoader $metaModelLoader,
        private readonly SqlRunnerInterface $sqlRunner,
        private readonly ContainerInterface $container,
        private readonly TranslatorInterface $translator,
        private readonly ProjectOverloader $projectOverloader,
    )
    {

    }

    public function getModelFromMetaObject(string $className, array $groups = []): DataReaderInterface
    {
        // check Cache first!

        return $this->createModel($className, $groups);
    }

    public function createModel(string $className, array $groups = []): FullDataInterface
    {
        if (!class_exists($className)) {
            throw new MetaModelException("Class $className does not exist");
        }

        $metaModel = new MetaModel($className, $this->metaModelLoader);

        $reflectionClass = new ReflectionClass($className);

        if ($tableInfo = $this->getTableInfo($reflectionClass)) {
            $model = new JoinModel($metaModel, $this->sqlRunner);

            $model->startJoin($tableInfo->name, !$tableInfo->readonly);

            $joins = $reflectionClass->getAttributes(JoinTable::class);
            foreach ($joins as $join) {
                $joinInfo = $join->newInstance();
                if (!$this->appliesTo($joinInfo->groups, $groups)) {
                    continue;
                }

                $model->addTable($joinInfo->tableName, $joinInfo->joinFields, !$joinInfo->readonly, $joinInfo->alias, $joinInfo->joinType === JoinType::INNER);
            }
        } else {
            $model = new PhpArrayModel($metaModel, new \ArrayObject());
        }
        $this->addMetaInfo($metaModel, $reflectionClass, $groups);

        $this->addTransformers($metaModel, $reflectionClass, $groups);

        return $model;
    }

    private function addMetaInfo(MetaModelInterface $metaModel, ReflectionClass $reflectionClass, array $groups): void
    {
        $tableInfo = $this->getTableInfo($reflectionClass);

        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return;
        }
        $parameters = $constructor->getParameters();
        foreach ($parameters as $parameter) {

            $name = $this->getFieldNameFromParameter($parameter, $tableInfo, $groups);
            if ($name === null) {
                continue;
            }

            $settings = $this->getFieldInfo($parameter, $tableInfo, $groups);

            if ($settings) {
                $metaModel->set($name, $settings);
            }

            if ($parameter->getName() !== $name) {
                $metaModel->setAlias($parameter->getName(), $name);
            }
        }
    }

    private function addTransformers(MetaModelInterface $metaModel, ReflectionClass $reflectionClass, array $groups): void
    {
        $transformerAttributes = $reflectionClass->getAttributes(Transformer::class);
        foreach ($transformerAttributes as $transformerAttribute) {
            $attribute = $transformerAttribute->newInstance();
            if (!$this->appliesTo($attribute->groups, $groups)) {
                continue;
            }
            $transformer = $this->metaModelLoader->createTransformer($attribute->class, ...$attribute->options);
            $metaModel->addTransformer($transformer);
        }
    }
    private function getFieldInfo(
        ReflectionParameter $parameter,
        ?Table $mainTableInfo = null,
        array $groups = [],
    ): array
    {
        $base = [];
        $grouped = [];

        $attributes = $parameter->getAttributes(MetaSetterInterface::class, \ReflectionAttribute::IS_INSTANCEOF);
        foreach ($attributes as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            $attributeGroups = $attribute->groups ?? null;
            if (!$this->appliesTo($attributeGroups, $groups)) {
                continue;
            }

            if ($attribute instanceof Custom) {
                $info = $attribute->options;
            } else {
                $info = get_object_vars($attribute);
                unset($info['groups']);
                $info = array_filter($info, function ($value) {
                    return $value !== null;
                });
            }

            if ($attributeGroups === null) {
                $base[] = $info;
            } else {
                $grouped[] = $info;
            }
        }

        $settings = array_merge([], ...$base, ...$grouped);
        return array_map($this->resolveValue(...), $settings);
    }

    private function resolveValue(mixed $value): mixed
    {
        if ($value instanceof Trans) {
            return $this->translator->trans($value->id);
        }

        if ($value instanceof CallableRef) {
            $object = $this->container->get($value->className);
            if (!method_exists($object, $value->methodName)) {
                throw new MetaModelException("Method $value->className::$value->methodName() does not exist");
            }
            return $object->{$value->methodName}();
        }
        return $value;
    }

    private function getFieldNameFromParameter(
        ReflectionParameter $parameter,
        ?Table $mainTableInfo = null,
        array $groups = [],
    ): ?string
    {
        $column = null;

        $columnAttributes = $parameter->getAttributes(Column::class);
        foreach($columnAttributes as $columnAttribute) {
            $columnInfo = $columnAttribute->newInstance();
            if (! $this->appliesTo($columnInfo->groups, $groups)) {
                continue;
            }

            if ($column === null || $columnInfo->groups !== null) {
                $column = $columnInfo;
            }
        }

        if ($column === null) {
            return null;
        }

        $name = $column->name ?? $parameter->getName();
        if ($column->prefix) {
            return $column->prefix . static::PREFIX_DELIMITER . $name;
        }
        if ($mainTableInfo?->prefix && ($column->table === null || $column->table === $mainTableInfo->name)) {
            return $mainTableInfo->prefix . static::PREFIX_DELIMITER . $name;
        }

        return $name;
    }

    private function getTableInfo(ReflectionClass $reflectionClass): ?Table
    {
        $tableAttributes = $reflectionClass->getAttributes(Table::class);
        foreach($tableAttributes as $tableAttribute) {
            /** @var Table $instance */
            $instance = $tableAttribute->newInstance();
            return $instance;
        }
        return null;
    }

    private function appliesTo(string|array|null $itemGroups, array $requestGroups): bool
    {
        if ($itemGroups === null || $requestGroups === []) {
            return true;
        }
        return (bool) array_intersect((array) $itemGroups, $requestGroups);
    }
}