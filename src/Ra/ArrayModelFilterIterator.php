<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Iterator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Ra;

/**
 * @package    Zalt
 * @subpackage Model\Iterator
 * @since      Class available since version 2.0
 */
class ArrayModelFilterIterator
{
    /**
     * The filter to apply
     *
     * @var array
     */
    protected $_filter;

    /**
     *
     * @var \Zalt\Model\Ra\ArrayModelAbstract
     */
    protected $_model;

    /**
     *
     * @param \Iterator $iterator
     * @param ArrayModelAbstract $model
     * @param array $filter
     */
    public function __construct(\Iterator $iterator, ArrayModelAbstract $model, array $filter)
    {
        parent::__construct($iterator);

        $this->_model = $model;
        $this->_filter = $filter;
    }

    /**
     *
     * @return boolean
     */
    public function accept()
    {
        return $this->_model->applyFiltersToRow($this->current(), $this->_filter);
    }
}