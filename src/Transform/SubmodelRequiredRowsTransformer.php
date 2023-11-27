<?php

namespace Zalt\Model\Transform;

use Zalt\Model\MetaModelInterface;

class SubmodelRequiredRowsTransformer extends RequiredRowsTransformer
{
    public function __construct(protected string $subModelName)
    {}

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        if ($model->has($this->subModelName, 'model')) {
            $subModel = $model->get($this->subModelName, 'model');
            foreach($data as $key => $row) {
                $result = parent::transformLoad($subModel->getMetaModel(), $row[$this->subModelName], $new, $isPostData);
                $data[$key][$this->subModelName] = $result;
            }
        }
        return $data;
    }
}