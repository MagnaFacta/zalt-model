<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Ra;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataReaderTrait;
use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\ModelException;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;

/**
 * The ArrayModelAbstract is a base class for alle classes that must load a whole array
 * and have the filtering and sorting done by PHP code. E.g. when reading lines of
 * of CSV file.
 *
 * The content is read whole by the function _loadAll() and then filtered or sorted
 * by the code in this abstract object.
 *
 * When the subclass implements the DataWriterInterface if should reimplement the _saveAll()
 * function and implement saving the whole set of data there.
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 2.0
 */
abstract class ArrayModelAbstract implements DataReaderInterface
{
    use DataReaderTrait;

    protected int $_changed = 0;

    private array $_sorts;

    public function __construct(
        protected MetaModelInterface $metaModel
    )
    {  }

    /**
     * Returns true if the passed row passed through the filter
     *
     * @param array $row A row of data
     * @param array $filters An array of filter statements
     * @param boolean $logicalAnd When true this is an AND filter, otherwise OR (switches at each array nesting level)
     * @return boolean
    */
    protected function _applyFiltersToRow(array $row, array $filters, bool $logicalAnd): bool
    {
        foreach ($filters as $name => $value) {
            if (is_callable($value)) {
                if (is_numeric($name)) {
                    $val = $row;
                } else {
                    $val = $row[$name] ?? null;
                }
                $result = call_user_func($value, $val);

            } elseif (is_array($value)) {
                $subFilter = true;
                if (1 == count($value)) {
                    if (isset($value[MetaModelInterface::FILTER_CONTAINS])) {
                        $result = str_contains($row[$name], $value[MetaModelInterface::FILTER_CONTAINS]);
                        $subFilter = false;
                    } elseif (isset($value[MetaModelInterface::FILTER_CONTAINS_NOT])) {
                        $result = ! str_contains($row[$name], $value[MetaModelInterface::FILTER_CONTAINS_NOT]);
                        $subFilter = false;
                    }
                } elseif (2 == count($value)) {
                    if (isset($value[MetaModelInterface::FILTER_BETWEEN_MAX], $value[MetaModelInterface::FILTER_BETWEEN_MIN])) {
                        $result = ($row[$name] >= $value[MetaModelInterface::FILTER_BETWEEN_MIN]) && ($row[$name] <= $value[MetaModelInterface::FILTER_BETWEEN_MAX]);
                        $subFilter = false;
                    }
                }
                if ($subFilter) {
                    if (is_numeric($name)) {
                        $result = $this->_applyFiltersToRow($row, $value, !$logicalAnd);
                    } elseif (MetaModelInterface::FILTER_NOT == $name) {
                        // Check here as NOT can be part of the main filter
                        $result = ! $this->_applyFiltersToRow($row, $value, ! $logicalAnd);
                    } else {
                        $rowVal = $row[$name] ?? null;
                        $result = false;
                        foreach ($value as $filterVal) {
                            if ($rowVal == $filterVal) {
                                $result = true;
                                break;
                            }
                        }
                    }
                }

            } else {
                if (is_numeric($name)) {
                    // Allow literal value interpretation
                    $result = (boolean) $value;
                } else {
                    $val    = isset($row[$name]) ? $row[$name] : null;
                    $result = ($val === $value) || (0 === strcasecmp($value, $val));
                }
                // \MUtil\EchoOut\EchoOut::r($value . '===' . $value . '=' . $result);
            }

            if ($logicalAnd xor $result) {
                return $result;
            }
        }

        // If $logicalAnd is true:
        //   => all filters must have triggered true to arrive here
        //   => the result is true,
        // If $logicalAnd is false:
        //   => all filters must have triggered false to arrive here
        //   => the result is false.
        return $logicalAnd;
    }

    /**
     * Filters the data array using a model filter
     *
     * @param \Traversable $data
     * @param array $filters
     * @return \Traversable
     */
    protected function _filterData($data, array $filters)
    {
        if ($data instanceof \IteratorAggregate) {
            $data = $data->getIterator();
        }

        // If nothing to filter
        if (! $filters) {
            return $data;
        }

        if ($data instanceof \Iterator) {
            return new ArrayModelFilterIterator($data, $this, $filters);
        }

        $filteredData = array();
        foreach ($data as $key => $row) {
            if ($this->_applyFiltersToRow($row, $filters, true)) {
                // print_r($row);
                $filteredData[] = $row;
            }
        }

        return $filteredData;
    }

    protected function _findCurrentRow(array $row, array $data, ?array $filter): mixed
    {
        $find = [];
        $keys = $this->metaModel->getKeys();
        if ($keys) {
            foreach ($keys as $field) {
                if (isset($row[$field])) {
                    $find[$field] = $row[$field];
                }
            }
        }
        if ($filter) {
            // Filter overrides current key values
            foreach ($filter as $field => $value) {
                $find[$field] = $value;
            }
        }

        if (! $find) {
            return null;
        }

        foreach ($data as $key => $currentRow) {
            $found = true;
            foreach ($find as $field => $value) {
                if ((! isset($currentRow[$field])) || $value != $currentRow[$field]) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                return $key;
            }
        }

        return null;
    }

    /**
     * An ArrayModel assumes that (usually) all data needs to be loaded before any load
     * action, this is done using the iterator returned by this function.
     *
     * @return array Return an array of all the rows in this object
     */
    abstract protected function _loadAll(): array;

    /**
     * When $this->_saveable is true a child class should either override the
     * delete() and save() functions of this class or override _saveAllTraversable().
     *
     * In the latter case this class will use _loadAllTraversable() and remove / add the
     * data to the data in the delete() / save() functions and pass that data on to this
     * function.
     *
     * @param array $data An array containing all the data that should be in this object
     * @return void
     */
    protected function _saveAll(array $data)
    {
        if ($this instanceof DataWriterInterface) {
            throw new ModelException(
                sprintf('Function "%s" should be overriden for class "%s".', __FUNCTION__, get_class($this))
            );
        }
        throw new ModelException(
            sprintf('Function "%s" may not be used for class "%s".', __FUNCTION__, get_class($this))
        );
    }

    /**
     * Sorts the output
     *
     * @param array $data
     * @param mixed $sorts
     * @return array
     */
    protected function _sortData(array $data, $sorts)
    {
        $this->_sorts = [];

        foreach ($sorts as $key => $order) {
            if (is_numeric($key) || is_string($order)) {
                if (strtoupper(substr($order,  -5)) == ' DESC') {
                    $order     = substr($order,  0,  -5);
                    $direction = SORT_DESC;
                } else {
                    if (strtoupper(substr($order,  -4)) == ' ASC') {
                        $order = substr($order,  0,  -4);
                    }
                    $direction = SORT_ASC;
                }
                $this->_sorts[$order] = $direction;

            } else {
                switch ($order) {
                    case SORT_DESC:
                        $this->_sorts[$key] = SORT_DESC;
                        break;

                    case SORT_ASC:
                    default:
                        $this->_sorts[$key] = SORT_ASC;
                        break;
                }
            }
        }

        usort($data, [$this, 'sortCmp']);

        return $data;
    }

    /**
     * Returns true if the passed row passed through the filter
     *
     * @param array $row A row of data
     * @param array $filters An array of filter statements
     * @return boolean
     */
    public function applyFiltersToRow(array $row, array $filters): bool
    {
        return $this->_applyFiltersToRow($row, $filters, true);
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter Null to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = null): int
    {
        if (! $this instanceof FullDataInterface) {
            throw new ModelException(
                sprintf('Function "%s" may not be used for class "%s" as it does not implement "%s".', __FUNCTION__, get_class($this), FullDataInterface::class)
            );
        }

        $data = $this->_loadAll();

        $deleting = $this->_filterData($data, $this->checkFilter($filter));

        foreach ($deleting as $row) {
            $key = $this->_findCurrentRow($row, $data, null);
            unset($data[$key]);
        }

        $this->_saveAll($data);

        return count($deleting);
    }

    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged(): int
    {
        return $this->_changed;
    }

    public function getName(): string
    {
        return $this->metaModel->getName();
    }

    public function hasNew() : bool
    {
        return $this instanceof DataWriterInterface;
    }

    /**
     * @inheritDoc
     */
    public function load($filter = null, $sort = null): array
    {
        $filter = $this->checkFilter($filter);
        $sort   = $this->checkSort($sort);

        $data = $this->_loadAll();

        if ($filter) {
            $data = $this->_filterData($data, $filter);
        }

        if (! is_array($data)) {
            $data = iterator_to_array($data);
        }

        if ($sort) {
            $data = $this->_sortData($data, $sort);
        }

        return $data;

    }

    /**
     * @inheritDoc
     */
    public function loadCount($filter = null, $sort = null): int
    {
        $output = $this->load($filter, $sort);
        return count($output);
    }

    /**
     * @inheritDoc
     */
    public function loadPageWithCount(?int &$total, int $page, int $items, $filter = null, $sort = null): array
    {
        $output = $this->load($filter, $sort);
        $total  = count($output);

        return array_slice($output, ($page - 1) * $items, $items);
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null): array
    {
        $data = $this->_loadAll();

        $key = $this->_findCurrentRow($newValues, $data, $filter);

        if (null !== $key) {
            // Copy ald values into row;
            $newValues = $newValues + $data[$key];
        }

        $beforeValues = $this->metaModel->processBeforeSave($newValues);
        if (null !== $key) {
            if ($beforeValues != $data[$key]) {
                $data[$key] = $beforeValues;
                $this->_changed++;
            }
        } else {
            $data[] = $beforeValues;
            $this->_changed++;
        }
        $this->_saveAll($data);
        $afterValues  = $this->metaModel->processAfterSave($beforeValues);

        if ($this->metaModel->getMeta(MetaModel::LOAD_TRANSFORMER) || $this->metaModel->hasDependencies()) {
            return $this->metaModel->processRowAfterLoad($afterValues, false);
        } else {
            return $afterValues;
        }


    }

    public function setChanged(int $changed = 0)
    {
        $this->_changed = $changed;
        return $this;
    }

    /**
     * Sort function for sorting array on defined sort order
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    public function sortCmp(array $a, array $b): int
    {
        foreach ($this->_sorts as $key => $direction) {
            if ($a[$key] !== $b[$key]) {
                // \MUtil\EchoOut\EchoOut::r($key . ': [' . $direction . ']' . $a[$key] . '-' . $b[$key]);
                if (SORT_ASC == $direction) {
                    return $a[$key] > $b[$key] ? 1 : -1;
                } else {
                    return $a[$key] > $b[$key] ? -1 : 1;
                }
            }
        }

        return 0;
    }
}