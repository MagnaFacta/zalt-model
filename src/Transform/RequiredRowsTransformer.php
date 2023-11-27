<?php

namespace Zalt\Model\Transform;

use Zalt\Model\Exception\ModelException;
use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;

class RequiredRowsTransformer extends ModelTransformerAbstract
{
    /**
     * Contains default values for all missing row values
     *
     * @var mixed Something that can be made into an array using Ra::to()
     */
    protected object|array|null $_defaultRow = null;

    /**
     * The number of key values to compare, if empty the number of fields in the first required row
     *
     * @var int
     */
    protected ?int $_keyItemCount = null;

    /**
     *
     * @var mixed Something that can be made into an array using Ra::to()
     */
    protected object|array|null $_requiredRows = null;

    /**
     *
     * @param array $required
     * @param array $row
     * @param int $count
     * @return boolean True if the rows refer to the same row
     */
    protected function _compareRows(array $required, array $row, int $count): bool
    {
        if ($row) {
            $val1 = reset($required);
            $key  = key($required);
            $val2 = $row[$key];
            $i = 0;
            while ($i < $count) {
                if ($val1 != $val2) {
                    return false;
                }
                $val1 = next($required);
                $val2 = next($row);
                $i++;
            }
            return true;

        } else {
            return false;
        }
    }

    /**
     * Returns the required rows set or calculates the rows using the $model and the required rows info
     *
     * @param MetaModelInterface $model Optional model for calculation
     * @return array
     * @throws ModelException
     */
    public function getDefaultRow(MetaModelInterface $model = null): array
    {
        if (! $this->_defaultRow) {
            $requireds = $this->getRequiredRows();
            $required  = Ra::to(reset($requireds));

            if (! $this->_keyItemCount) {
                $this->setKeyItemCount(count($required));
            }

            if ($required) {
                $defaults = [];
                foreach ($model->getItemNames() as $name) {
                    if (! array_key_exists($name, $required)) {
                        $defaults[$name] = null;
                    }
                }
                $this->_defaultRow = $defaults;
            } else {
                throw new ModelException('Cannot create default row without model and required rows.');
            }
        } elseif (! is_array($this->_defaultRow)) {
            $this->_defaultRow = Ra::to($this->_defaultRow);
        }

        return $this->_defaultRow;
    }

    /**
     * The number of key values to compare
     *
     * @return int
     */
    public function getKeyItemCount(): int
    {
        if (! $this->_keyItemCount) {
            $requiredRows = $this->getRequiredRows();
            $required = Ra::to(reset($requiredRows));
            $this->setKeyItemCount(count($required));
        }

        return $this->_keyItemCount;
    }

    /**
     * Array of required rows
     *
     * @return array
     */
    public function getRequiredRows(): array
    {
        if (! is_array($this->_requiredRows)) {
            $this->_requiredRows = Ra::to($this->_requiredRows);
        }

        return $this->_requiredRows;
    }

    /**
     * Contains default values for all missing row values
     *
     * @param mixed $defaultRow Something that can be made into an array using Ra::to()
     * @return self
     * @throws ModelException
     */
    public function setDefaultRow(object|array $defaultRow): self
    {
        if (Ra::is($defaultRow)) {
            $this->_defaultRow = $defaultRow;
            return $this;
        }

        throw new ModelException('Invalid parameter type for ' . __FUNCTION__ . ': $rows cannot be converted to an array.');
    }

    /**
     * The number of key values to compare
     *
     * @param int $count
     * @return self
     */
    public function setKeyItemCount($count)
    {
        $this->_keyItemCount = $count;
        return $this;
    }

    /**
     * The keys for the required rows
     *
     * @param mixed $rows Something that can be made into an array using Ra::to()
     * @return RequiredRowsTransformer
     * @throws ModelException
     */
    public function setRequiredRows(object|array $rows): self
    {
        if (Ra::is($rows)) {
            $this->_requiredRows = $rows;
            return $this;
        }

        throw new ModelException('Invalid parameter type for ' . __FUNCTION__ . ': $rows cannot be converted to an array.');
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
        $defaults  = $this->getDefaultRow($model);
        $keyCount  = $this->getKeyItemCount();
        $requireds = $this->getRequiredRows();
        $data      = Ra::to($data, Ra::RELAXED);
        $results   = array();
        if (! $data) {
            foreach ($requireds as $key => $required) {
                $results[$key] = $required + $defaults;
            }
        } else {
            foreach($requireds as $key => $required) {
                $exists = false;
                foreach($data as $row) {
                    if ($this->_compareRows($required, $row, $keyCount)) {
                        $results[$key] = $row + $required;
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $results[$key] = $required + $defaults;
                }
            }
        }

        return $results;
    }
}