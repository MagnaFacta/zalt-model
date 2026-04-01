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
    private array $propertyAttributes = [
        Column::class,
    ];

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

        return $this->createModel($className);
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
                $model->addTable($join->tableName, $join->joinFields, !$join->readonly, $join->alias, $join->joinType === JoinType::INNER);
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

            $metaModel->set($name, $settings);

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
            if ($attribute->groups && $this->isInGroup($groups, $attribute->groups)) {
                continue;
            }
            $transformer = $this->projectOverloader->create($attribute->className, ...$attribute->options);
            $metaModel->addTransformer($transformer);
        }
    }
    private function getFieldInfo(
        ReflectionParameter $parameter,
        Table $mainTableInfo,
        array $groups = [],
    ): array
    {
        $settings = [];

        $attributes = $parameter->getAttributes();
        foreach ($attributes as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            if (property_exists($attribute, 'groups' && $attribute->groups !== null && !$this->isInGroup($groups, (array)$attribute->groups))) {
                continue;
            }

            if ($attribute instanceof Custom) {
                $settings = array_merge($settings, $attribute->options);
                continue;
            }
            if (!$attribute instanceof MetaSetterInterface) {
                continue;
            }

            $attributeInfo = get_object_vars($attribute);
            foreach($attributeInfo as $attributeName => $attributeValue) {
                if ($attributeValue instanceof Trans) {
                    $attributeInfo[$attributeName] = $this->translator->trans($attributeValue->id);
                }
                if ($attributeValue instanceof CallableRef) {
                    $this->projectOverloader->create($attributeValue->className);
                    $class = $this->container->get($attributeValue->className);
                    if (method_exists($class, $attributeValue->methodName)) {
                        $attributeInfo[$attributeName] = $class->{$attributeValue->methodName}();
                    }
                }
            }
            $settings = array_merge($settings, $attributeInfo);
        }

        return $settings;
    }

    private function getFieldNameFromParameter(
        ReflectionParameter $parameter,
        Table|null $mainTableInfo = null,
        array $groups = [],
    ): ?string
    {

        $columnAttributes = $parameter->getAttributes(Column::class);
        foreach($columnAttributes as $columnAttribute) {
            $columnInfo = $columnAttribute->newInstance();

            if ($columnInfo->groups !== null && $this->isInGroup($groups, (array)$columnInfo->groups)) {
                continue;
            }

            if ($columnInfo->prefix) {
                return $columnInfo->prefix . $columnInfo->name;
            }
            if ($mainTableInfo && $columnInfo->table && $mainTableInfo->name === $columnInfo->table && $mainTableInfo->prefix) {
                return $mainTableInfo->prefix . $columnInfo->name;
            }
            return $columnInfo->name;
        }

        return null;
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

    private function isInGroup(array $requestedGroups, array $itemGroups): bool
    {
        return !empty(array_intersect($requestedGroups, $itemGroups));
    }
}