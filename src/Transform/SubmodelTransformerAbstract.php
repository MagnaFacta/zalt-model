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
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Transformer
 * @since      Class available since version 1.0
 */
abstract class SubmodelTransformerAbstract implements ModelTransformerInterface
{
    /**
     * The number of rows changed at the last save
     *
     * @var int
     */
    protected int $_changed = 0;

    /**
     *
     * @var array of join functions
     */
    protected array $_joins = [];

    /**
     *
     * @var DataReaderInterface[]
     */
    protected array $_subModels = [];

    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged(): int
    {
        return $this->_changed;
    }

    /**
     * Add an (extra) model to the join
     *
     * @param DataReaderInterface $subModel
     * @param array $joinFields
     * @return SubmodelTransformerAbstract (continuation pattern)
     */
    public function addModel(DataReaderInterface $subModel, array $joinFields, $name = null)
    {
        if (null === $name) {
            $name = $subModel->getMetaModel()->getName();
        }

        $this->_subModels[$name] = $subModel;
        $this->_joins[$name]     = $joinFields;

        return $this;
    }

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
            $subMetaModel = $sub->getMetaModel();
            foreach ($subMetaModel->getItemNames() as $name) {
                if (! $model->has($name)) {
                    $data[$name] = $subMetaModel->get($name);
                    $data[$name]['no_text_search'] = true;

                    // Remove unsuited data
                    unset($data[$name]['table'], $data[$name]['column_expression']);
                }
            }
        }
        return $data;
    }

    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are the result
     *
     * @param MetaModelInterface $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(MetaModelInterface $model, array $filter)
    {
        // Make sure the join fields are in the result set
        foreach ($this->_joins as $joins) {
            foreach ($joins as $source => $target) {
                if (!is_integer($source)) {
                    $model->get($source);
                }
            }
        }

        foreach ($this->_subModels as $name => $sub) {
            $filter = $this->transformFilterSubModel($model, $sub, $filter, $this->_joins[$name]);
        }

        return $filter;
    }

    /**
     * Filter
     *
     * @param MetaModelInterface $model
     * @param DataReaderInterface $sub
     * @param array $filter
     * @param array $joins
     * @return array
     */
    public function transformFilterSubModel(MetaModelInterface $model, DataReaderInterface $sub, array $filter, array $join)
    {
        return $filter;
    }

    /**
     * The transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param MetaModelInterface $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        if (! $data) {
            return $data;
        }

        foreach ($this->_subModels as $name => $sub) {
            $this->transformLoadSubModel($model, $sub, $data, $this->_joins[$name], $name, $new, $isPostData);
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
    abstract protected function transformLoadSubModel(MetaModelInterface $model, DataReaderInterface $sub, array &$data, array $join, string $name, bool $new, bool $isPostData);

    /**
     * This transform function performs the actual save (if any) of the transformer data and is called after
     * the saving of the data in the source model.
     *
     * @param MetaModelInterface $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(MetaModelInterface $model, array $row)
    {
        if (! $row) {
            return $row;
        }

        foreach ($this->_subModels as $name => $sub) {
            $this->transformSaveSubModel($model, $sub, $row, $this->_joins[$name], $name);
            if ($sub instanceof DataWriterInterface) {
                $this->_changed = $this->_changed + $sub->getChanged();
            }
        }
        // \MUtil\EchoOut\EchoOut::track($row);

        return $row;
    }

    /**
     * This transform function is called before the saving of the data in the source model and allows you to
     * change all data.
     *
     * @param MetaModelInterface $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowBeforeSave(MetaModelInterface $model, array $row)
    {
        // No changes
        return $row;
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
    abstract protected function transformSaveSubModel(MetaModelInterface $model, FullDataInterface $sub, array &$row, array $join, string $name);

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
            foreach ($sort as $key => $value) {
                if ($sub->getMetaModel()->has($key)) {
                    // Remove all sorts on columns from the submodel
                    unset($sort[$key]);
                }
            }
        }

        return $sort;
    }

    /**
     * When true, the on save functions are triggered before passing the data on
     *
     * @return boolean
     */
    public function triggerOnSaves()
    {
        return false;
    }
}