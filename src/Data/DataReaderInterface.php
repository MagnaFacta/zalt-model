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

use Zalt\Late\RepeatableInterface;
use Zalt\Model\Bridge\BridgeInterface;

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
     * Create the bridge for the specific idenitifier
     *
     * This will always be a new bridge because otherwise you get
     * instabilities as bridge objects are shared without knowledge
     *
     * @param string $identifier
     * @param array $parameters Optional first of extra arguments
     * @return \Zalt\Model\Bridge\BridgeInterface
     */
    public function getBridgeFor($identifier, ...$parameters): BridgeInterface;

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
     * Does the model have a filter?
     *
     * @return boolean
     */
    public function hasFilter(): bool;

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    public function hasNew(): bool;

    /**
     * Does the model have a sort?
     *
     * @return boolean
     */
    public function hasSort(): bool;

    /**
     * Returns a nested array containing the items requested.
     *
     * @param mixed $filter Array to use as filter
     * @param mixed $sort Array to use for sort
     * @return array Nested array or empty
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
     * Returns the numbers of rows with the items requested
     *
     *
     * @param int|null $total
     * @param int $page The page number starting with the offset number ONE, not zero
     * @param int $items The number of items per page (and thus the number of items returned)
     * @param mixed $filter Array to use as filter
     * @param mixed $sort Array to use for sort
     * @return array Nested array or empty
     */
    public function loadPageWithCount(?int &$total, int $page, int $items, $filter = null, $sort = null): array;

    /**
     * Returns a \MUtil\Lazy\RepeatableInterface for the items in the model
     *
     * @param mixed $filter Null to use the stored filter, array to specify a different filter
     * @param mixed $sort Null to use the stored sort, array to specify a different sort
     * @return ?\Zalt\Late\RepeatableInterface
     */
    public function loadRepeatable($filter = null, $sort = null): ?RepeatableInterface;

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
    public function setSort(array $sort): DataReaderInterface;
}
