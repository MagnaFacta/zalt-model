<?php

namespace Zalt\Model\Transform;

use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;

class OneToManyMultiOptionsTransformer extends OneToManyTransformer
{
    protected function transformSaveSubModel(
        MetaModelInterface $model,
        FullDataInterface $sub,
        array &$row,
        array $join,
        string $name
    ) {
        if (! isset($row[$name])) {
            return;
        }

        foreach($row[$name] as $subKey => $subId) {
            $keys = $sub->getMetaModel()->getKeys();
            if (count($keys) === 1) {
                $keyField = reset($keys);
                $row[$name][$subKey] = [
                    $keyField => $subId
                ];
            }
        }
        
        parent::transformSaveSubModel($model, $sub, $row, $join, $name);
    }
}