<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Transformer
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Transform;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Transformer
 * @since      Class available since version 1.0
 */
class NestedTransformer extends SubmodelTransformerAbstract
{
    /**
     * Set to true when a submodel should not be saved
     *
     * @var boolean
     */
    public bool $skipSave = false;

    /**
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param MetaModelInterface $model The parent model
     * @return array Of filedname => set() values
     */
    public function getFieldInfo(MetaModelInterface $model)
    {
        $data = array();
        foreach ($this->_subModels as $sub) {
            $metaModel = $sub->getMetaModel();
            foreach ($metaModel->getItemNames() as $name) {
                if (! $metaModel->has($name)) {
                    $data[$name] = $metaModel->get($name);
                    $data[$name]['no_text_search'] = true;

                    // Remove unsuited data
                    unset($data[$name]['table'], $data[$name]['column_expression']);
                    unset($data[$name]['label']);
                    $data[$name]['elementClass'] = 'None';

                    // Remove the submodel's own transformers to prevent changed/created to show up in the data array instead of only in the nested info
                    unset($data[$name][MetaModel::LOAD_TRANSFORMER]);
                    unset($data[$name][MetaModel::SAVE_TRANSFORMER]);
                }
            }
        }
        return $data;
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MetaModelInterface $model Parent model
     * @param DataReaderInterface $sub Sub model
     * @param array $data The nested data rows
     * @param array $join The join array
     * @param string $name Name of sub model
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     */
    protected function transformLoadSubModel(MetaModelInterface $model, DataReaderInterface $sub, array &$data, array $join, string $name, bool $new, bool $isPostData)
    {
        foreach ($data as $key => $row) {
            // E.g. if loaded from a post
            if (isset($row[$name])) {
                $rows = $sub->getMetaModel()->processAfterLoad($row[$name], $new, $isPostData);
            } elseif ($new) {
                $rows = $sub->loadNew();
            } else {
                $filter = $sub->getFilter();

                foreach ($join as $parent => $child) {
                    if (isset($row[$parent])) {
                        $filter[$child] = $row[$parent];
                    }
                }
                // If $filter is empty, treat as new
                if (empty($filter)) {
                    $rows = $sub->loadNew();
                } else {
                    $rows = $sub->load($filter);
                }
            }

            $data[$key][$name] = $rows;
        }
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MetaModelInterface $model
     * @param FullDataInterface $sub
     * @param array $row
     * @param array $join
     * @param string $name
     */
    protected function transformSaveSubModel(
        MetaModelInterface $model,
        FullDataInterface $sub,
        array &$row,
        array $join,
        string $name)
    {
        if ($this->skipSave) {
            return;
        }

        if (! isset($row[$name])) {
            return;
        }

        $data = $row[$name];
        $keys = array();

        // Get the parent key values.
        foreach ($join as $parent => $child) {
            if (isset($row[$parent])) {
                $keys[$child] = $row[$parent];
            } else {
                // if there is no parent identifier set, don't save
                return;
            }
        }
        foreach($data as $key => $subrow) {
            // Make sure the (possibly changed) parent key
            // is stored in the sub data.
            $data[$key] = $keys + $subrow;
        }

        $saved = [];
        foreach ($data as $key => $row) {
            $saved[$key] = $sub->save($row);
        }

        $row[$name] = $saved;
    }

    /**
     * This transform function checks the sort to
     * a) remove sorts from the main model that are not possible
     * b) add sorts that are required needed
     *
     * @param MetaModelInterface $model
     * @param array $sort
     * @return array The (optionally changed) sort
     */
    public function transformSort(MetaModelInterface $model, array $sort)
    {
        foreach ($this->_subModels as $sub) {
            $subSorts = [];
            foreach ($sort as $key => $value) {
                if ($sub->getMetaModel()->has($key)) {
                    $subSorts[$key] = $value;
                }
            }
            if ($subSorts) {
                $sub->setSort($subSorts + $sub->getSort());
            }
        }

        return $sort;
    }
}