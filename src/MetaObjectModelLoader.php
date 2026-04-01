<?php

namespace Zalt\Model;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionParameter;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\MetaModelException;
use Zalt\Model\Mapping\Database\Column;
use Zalt\Model\Mapping\Database\JoinTable;
use Zalt\Model\Mapping\Database\JoinType;
use Zalt\Model\Mapping\Database\Table;
use Zalt\Model\Mapping\Form\FormElement;
use Zalt\Model\Ra\PhpArrayModel;
use Zalt\Model\Sql\JoinModel;
use Zalt\Model\Sql\SqlRunnerInterface;

class MetaObjectModelLoader
{
    public function __construct(
        private readonly ObjectModelRepository $objectModelRepository,
    )
    {
    }

    public function createModel(string $className, array $groups = []): FullDataInterface
    {
        return $this->objectModelRepository->createModel($className, $groups);
    }
}