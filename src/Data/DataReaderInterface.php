<?php

/**
 *
 * @package    Zalt
 * @subpackage Model\Data
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Zalt\Model\Data;

/**
 *
 * @package    Zalt
 * @subpackage Model\Data
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 14-Aug-2018 16:48:46
 */
interface DataReaderInterface extends \Zalt\Model\MetaModellerInterface
{
    /**
     * Merges this filter with the default filter.
     *
     * Filters having field names as key should intersect with any previously set values set on
     * the same field.
     *
     * Filters with with a numerical index are just added to the filter.
     *
     * @param array $filter
     * @return \Zalt\Model\Data\DataReaderInterface (continuation pattern)
     */
    public function addFilter(array $value): DataReaderInterface;

    /**
     * Add's one or more sort fields to the standard sort.
     *
     * @param mixed $value Array of sortfield => SORT_ASC|SORT_DESC or single sortfield for ascending sort.
     * @return \Zalt\Model\Data\DataReaderInterface (continuation pattern)
     */
    public function addSort($value): DataReaderInterface;

    /**
     * Get the current default filter for save/loade
     * @return array
     */
    public function getFilter(): array;

    /**
     * Get the current default sort for save/loade
     * @return array
     */
    public function getSort(): array;

    /**
     * @return string The sort value for and ascending parameter
     */
    public function getSortParamAsc(): string;

    /**
     * @return string The sort value for and descending parameter
     */
    public function getSortParamDesc(): string;

    /**
     * @return string The parameter used to store the text search value in
     */
    public function getTextFilter(): string;

    /**
     * Splits a wildcard search text into its constituent parts.
     *
     * @param string $searchText
     * @return array
     */
    public function getTextSearches($searchText);

    /**
     * Creates a filter for this model for the given wildcard search text.
     *
     * @param string $searchText
     * @return array An array of filter statements for wildcard text searching for this model type
     */
    public function getTextSearchFilter($searchText);

    /**
     * Does the model have a filter?
     *
     * @return boolean
     */
    public function hasFilter(): boolean;

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    public function hasNew(): boolean;

    /**
     * Does the model have a sort?
     *
     * @return boolean
     */
    public function hasSort(): boolean;

    /**
     * True when the model supports general text filtering on all
     * labelled fields.
     *
     * This must be implemented by each sub model on it's own.
     *
     * @return boolean
     */
    public function hasTextSearchFilter(): boolean;

    /**
     * Returns a nested array containing the items requested.
     *
     * @param mixed $filter Array to use as filter
     * @param mixed $sort Array to use for sort
     * @return array Nested array or false
     */
    public function load($filter = null, $sort = null): array;

    /**
     * Returns an array containing the first requested item.
     *
     * @param mixed $filter Array to use as filter
     * @param mixed $sort Array to use for sort
     * @return array An array or false
     */
    public function loadFirst($filter = null, $sort = null): array;

    /**
     * Creates new items - in memory only.
     *
     * @return array Nested when $count is not null, otherwise just a simple array
     */
    public function loadNew(): array;

    /**
     * Sets a default filter to be used when no filter was passed to a load() or loadX() function.
     *
     * Standard filters are arrays containing field names as key and a single value or an array
     * of values and load only those rows that have the same value or is that are contained in
     * the value arrays.
     *
     * Filters with with a numerical index should be child model specific filters. E.g. database
     * based models may allow SQL expressions while array based models may use callable functions
     * with the whole row as the parameter value.
     *
     * @param array $filter
     * @return \Zalt\Model\Data\DataReaderInterface (continuation pattern)
     */
    public function setFilter(array $filter): DataReaderInterface;

    /**
     * (re)set the current sorting
     * 
     * @param $value
     * @return \Zalt\Model\Data\DataReaderInterface (continuation pattern)
     */
    public function setSort($value): DataReaderInterface;

    /**
     * @param string $value The sort value for and ascending parameter
     * @return \Zalt\Model\Data\DataReaderInterface (continuation pattern)
     */
    public function setSortParamAsc(string $value): DataReaderInterface;

    /**
     * @param string $value The sort value for and descending parameter
     * @return \Zalt\Model\Data\DataReaderInterface (continuation pattern)
     */
    public function setSortParamDesc(string $value): DataReaderInterface;

    /**
     * @param string $value The parameter used to store the text search value in
     * @return \Zalt\Model\Data\DataReaderInterface (continuation pattern)
     */
    public function setTextFilter(string $value): DataReaderInterface;
}
