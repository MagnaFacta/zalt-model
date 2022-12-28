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
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataWriterInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class MetaModelLoader 
{
    public function __construct(protected ProjectOverloader $loader)
    {
        $this->loader->setDependencyResolver(new ConstructorDependencyParametersResolver());
    }
    
    public function createModel(string $className, mixed $metaModelName, mixed ...$parameters): DataReaderInterface|DataWriterInterface
    {
        if (! is_string($metaModelName)) {
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
            $metaModel = new MetaModel($metaModelName);
            array_unshift($parameters, $metaModel);
        }
        
        return $this->loader->create($className, ...$parameters);
    }
}