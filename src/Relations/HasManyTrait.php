<?php

namespace Zalt\Model\Relations;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\OneToManyTransformer;

trait HasManyTrait
{
    public function hasMany(DataReaderInterface|string $model, array|string $joins, string|null $name = null): void
    {
        if (is_string($model) && class_exists($model)) {
            $model = $this->getMetaModel()->getMetaModelLoader()->createModel($model);
        }

        $parentMetaModel = $this->getMetaModel();

        $keys = $parentMetaModel->getKeys();
        if (is_string($joins) && count($keys) === 1) {
            $parentJoinField = reset($keys);
            $joins = [$parentJoinField => $joins];
        }

        $parentMetaModel->addTransformer(new OneToManyTransformer($model, $joins, $name));
        $parentMetaModel->set($name, [
            'model' => $model,
            'type' => MetaModelInterface::TYPE_CHILD_MODEL,
        ]);
    }
}