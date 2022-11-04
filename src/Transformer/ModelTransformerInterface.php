<?php

// declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Transformer
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Transformer;

use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Transformer
 * @since      Class available since version 1.0
 */
interface ModelTransformerInterface
{
    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged();

    /**
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param \Zalt\Model\MetaModelInterface $model The parent model
     * @return array Of filedname => set() values
     */
    public function getFieldInfo(MetaModelInterface $model);

    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param \Zalt\Model\MetaModelInterface $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(MetaModelInterface $model, array $filter);

    /**
     * This transform function checks the sort to
     * a) remove sorts from the main model that are not possible
     * b) add sorts that are required needed
     *
     * @param \Zalt\Model\MetaModelInterfacet $model
     * @param array $sort
     * @return array The (optionally changed) sort
     */
    public function transformSort(MetaModelInterface $model, array $sort);

    /**
     * This transform function performs the actual transformation of the data and is called after
     * the loading of the data in the source model.
     *
     * @param \Zalt\Model\MetaModelInterface $model The parent model
     * @param array $data Nested array
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Nested array containing (optionally) transformed data
     */
    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false);

    /**
     * This transform function performs the actual save (if any) of the transformer data and is called after
     * the saving of the data in the source model.
     *
     * @param \Zalt\Model\MetaModelInterface $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(MetaModelInterface $model, array $row);

    /**
     * This transform function is called before the saving of the data in the source model and allows you to
     * change all data.
     *
     * @param \Zalt\Model\MetaModelInterface $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowBeforeSave(MetaModelInterface $model, array $row);

    /**
     * When true, the on save functions are triggered before passing the data on
     *
     * @return boolean
     */
    public function triggerOnSaves();
}