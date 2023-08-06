<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

use Zalt\Loader\DependencyResolver\ConstructorDependencyParametersResolver;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\Dependency\DependencyInterface;
use Zalt\Model\Transform\ModelTransformerInterface;
use Zalt\Model\Type\ModelTypeInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class MetaModelLoader 
{
    public function __construct(
        protected ProjectOverloader $loader, 
        protected array $modelConfig,
    )
    {
        $this->loader->setDependencyResolver(new ConstructorDependencyParametersResolver());
    }

    public function createBridge($class, DataReaderInterface $dataModel, ...$parameters): BridgeInterface
    {
        if (isset($this->modelConfig['bridges'][$class])) {
            $class = $this->modelConfig['bridges'][$class];
        }

        return $this->loader->create($class, $dataModel, ...$parameters);
    }

    public function createDependency($dependencyClass, ...$parameters): DependencyInterface
    {
        return $this->loadSubType($dependencyClass, 'Dependency', ...$parameters);
    }

    public function createMetaModel($metaModelName): MetaModelInterface
    {
        return new MetaModel($metaModelName, $this);
    }
    
    public function createModel(string $className, mixed $metaModelName = null, mixed ...$parameters): DataReaderInterface|DataWriterInterface
    {
        if (null === $metaModelName) {
            $metaModelName = isset($className::$modelName) ? $className::$modelName :$className;

        } elseif (! is_string($metaModelName)) {
            array_unshift($parameters, $metaModelName);
            $metaModelName = $className;
        }
        $metaModel = null;
        foreach ($parameters as $key => $parameter) {
            if ($parameter instanceof MetaModelInterface) {
                $metaModel = $parameter;
            }
        }
        if (null === $metaModel) {
            $metaModel = $this->createMetaModel($metaModelName);
            array_unshift($parameters, $metaModel);
        }
        
        return $this->loader->create($className, ...$parameters);
    }
    
    public function createTransformer(string $class, ...$parameters): ModelTransformerInterface
    {
        return $this->loadSubType($class, 'Transform', ...$parameters);
    }

    public function createType(string $class, ...$parameters)
    {
        return $this->loadSubType($class, 'Tyoe', ...$parameters);
    }

    public function getDefaultTypeInterface(int $type): ?ModelTypeInterface
    {
        if (isset($this->modelConfig['modelTypes'][$type])) {
            $class = $this->modelConfig['modelTypes'][$type];
            if (! $class instanceof ModelTypeInterface) {
                $class = $this->createType($class);
            }
            $this->modelConfig['modelTypes'][$type] = $class;
            return $class;
        }

        return null;
    }
    
    protected function loadSubType(string $class, string $subType, ...$parameters)
    {
        if (! (str_contains($class, "\\$subType\\") || str_starts_with($class, $subType . '\\'))) {
            if (! class_exists($class)) {
                $class = $subType . '\\' . $class;
            }
        }
        return $this->loader->create($class, ...$parameters);
    }
}
