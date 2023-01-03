<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Transformer
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;

/**
 *
 * @package    Zalt
 * @subpackage Model\Transformer
 * @since      Class available since version 1.0
 */
abstract class ModelTransformerAbstract implements ModelTransformerInterface
{
    /**
     *
     * @var array
     */
    protected array $_fields = [];

    /**
     * Gets one or more values for a certain field name.
     *
     * @see \MUtil\Model\ModelAbstract->get()
     *
     * @param string $name Field name
     * @param array $args Zalt\Ra:args Null or an array of attributenames
     * @return mixed
     */
    public function get(string $name, ...$args): mixed
    {
        $args = Ra::args($args);

        switch (count($args)) {
            case 0:
                if (isset($this->_fields[$name])) {
                    return $this->_fields[$name];
                } else {
                    return array();
                }

            case 1:
                $key = key($args);
                if (isset($this->_fields[$name][$key])) {
                    return $this->_fields[$name][$key];
                } else {
                    return null;
                }

            default:
                $results = array();
                foreach ($args as $key) {
                    if (isset($this->_fields[$name][$key])) {
                        $results[$key] = $this->_fields[$name][$key];
                    }
                }
                return $results;
        }
    }

    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged(): int
    {
        return 0;
    }

    /**
     * If the transformer add's fields, these should be returned here.
     * Called in $model->AddTransformer(), so the transformer MUST
     * know which fields to add by then (optionally using the model
     * for that).
     *
     * @param \MUtil\Model\ModelAbstract $model The parent model
     * @return array Of fieldname => set() values
     */
    public function getFieldInfo(MetaModelInterface $model): array
    {
        return $this->_fields;
    }

    /**
     * Set one or more attributes for a field names in the model.
     *
     * @param string $name The fieldname
     * @param array $args Zalt\Ra:args Null or an array of attributenames
     * @return \Zalt\Model\Transform\ModelTransformerAbstract
     *@see \MUtil\Model\ModelAbstract->set()
     *
     */
    public function set(string $name, ...$args): ModelTransformerAbstract
    {
        $args = Ra::pairs($args);

        if ($args) {
            foreach ($args as $key => $value) {
                // If $key end with ] it is array value
                if (substr($key, -1) == ']') {
                    if (substr($key, -2) == '[]') {
                        // If $key ends with [], append it to array
                        $key    = substr($key, 0, -2);
                        $this->_fields[$name][$key][] = $value;
                    } else {
                        // Otherwise extract subkey
                        $pos    = strpos($key, '[');
                        $subkey = substr($key, $pos + 1, -1);
                        $key    = substr($key, 0, $pos);

                        $this->_fields[$name][$key][$subkey] = $value;
                    }
                } else {
                    $this->_fields[$name][$key] = $value;
                }
            }
        } elseif (!array_key_exists($name, $this->_fields)) {
            $this->_fields[$name] = array();
        }

        return $this;
    }

    /**
     * This transform function checks the filter for
     * a) retreiving filters to be applied to the transforming data,
     * b) adding filters that are needed
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $filter
     * @return array The (optionally changed) filter
     */
    public function transformFilter(MetaModelInterface $model, array $filter)
    {
        // No changes
        return $filter;
    }

    /**
     * This transform function checks the sort to
     * a) remove sorts from the main model that are not possible
     * b) add sorts that are required needed
     *
     * @param \MUtil\Model\ModelAbstract $model
     * @param array $sort
     * @return array The (optionally changed) sort
     */
    public function transformSort(MetaModelInterface $model, array $sort)
    {
        // No changes
        return $sort;
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
        // No changes
        return $data;
    }

    /**
     * This transform function performs the actual save (if any) of the transformer data and is called after
     * the saving of the data in the source model.
     *
     * @param \MUtil\Model\ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowAfterSave(MetaModelInterface $model, array $row)
    {
        // No changes
        return $row;
    }

    /**
     * This transform function is called before the saving of the data in the source model and allows you to
     * change all data.
     *
     * @param \MUtil\Model\ModelAbstract $model The parent model
     * @param array $row Array containing row
     * @return array Row array containing (optionally) transformed data
     */
    public function transformRowBeforeSave(MetaModelInterface $model, array $row)
    {
        // No changes
        return $row;
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