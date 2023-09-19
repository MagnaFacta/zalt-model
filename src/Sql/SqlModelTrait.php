<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModel;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
trait SqlModelTrait
{
    /**
     * @var int The number of changed rows
     */
    protected int $changed = 0;
    
    /**
     * A standard rename scaffold for hidden kopies of primary key fields.
     *
     * As this is the name a hidden \Zend_Element we can use only letters and the underscore for
     * the first character and letters, the underscore and numbers for the later characters.
     *
     * \Zend_Element allows some other extended characters, but those may not work
     * with some browsers.
     *
     * @var string $keyKopier String into which the original keyname is sprintf()-ed.
     */
    protected $keyCopier = '__c_1_3_copy__%s__key_k_0_p_1__';

    protected array|null $oldValues = null;

    protected function addChanged()
    {
        $this->changed++;
    }


    /**
     * Adds a column to the model
     *
     * @param mixed $column Usually string but might also be a SQL Expression object
     * @param ?string $columnName
     * @param ?string $orignalColumn
     * @return \Zalt\Model\Data\FullDataInterface Provides a fluent interface
     */
    public function addColumn(mixed $column, string $columnName = null, string $orignalColumn = null)
    {
        if (null === $columnName) {
            $columnName = strtr((string) $column, ' .,;:?!\'"()<=>-*+\\/&%^', '______________________');
        }
        if ($orignalColumn) {
            $this->metaModel->setAlias($columnName, $orignalColumn);
        }
        $this->metaModel->set($columnName, 'column_expression', $column);

        return $this;
    }

    /**
     * Makes a copy for each key item in the model using $this->getKeyCopyName()
     * to create the new name.
     *
     * Call this function whenever the user is able to edit a key and the key is not
     * stored elsewhere (e.g. in a parameter). The save function using this value to
     * perform an update instead of an insert on a changed key.
     *
     * @param boolean $reset True if the key list should be rebuilt.
     * return \MUtil\Model\DatabaseModelAbstract $this
     */
    public function copyKeys($reset = false)
    {
        foreach ($this->metaModel->getKeys($reset) as $name) {
            $this->addColumn($name, $this->getKeyCopyName($name), $name);
        }
        return $this;
    }

    /**
     * Filters the list of values and returns only those that should be used for this table.
     *
     * @param string $tableName The current table
     * @param array $data All the data, including those for other tables
     * @param boolean $isNew True when creating
     * @return array An array containting the values that should be saved for this table.
     */
    protected function filterDataForTable($tableName, array $data, $isNew)
    {
        $output = [];

        // First find the correct fields to save
        foreach ($this->metaModel->getItemsFor(['table' => $tableName]) as $name) {
            if (array_key_exists($name, $data) && (! $this->metaModel->has($name, 'column_expression'))) {
                $len = intval($this->metaModel->get($name, 'maxlength'));
                if ($len && $data[$name] && (! is_array($data[$name]))) {
                    $output[$name] = substr((string) $data[$name], 0, $len);
                } else {
                    $output[$name] = $data[$name];
                }

            } elseif ($this->metaModel->isAutoSave($name)) {
                // Add a value for on auto save values
                $output[$name] = null;
            }
        }
        return $this->metaModel->processRowBeforeSave($output, $isNew, $data);
    }

    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged(): int
    {
        return $this->changed;
    }

    /**
     * Returns the key copy name for a field.
     *
     * @param string $name
     * @return string
     */
    public function getKeyCopyName($name)
    {
        return sprintf($this->keyCopier, $name);
    }
    
    /**
     * @param string $tableName  Does not test for existence
     * @return array array int => name  containing the key field names.
     */
    protected function getKeysForTable($tableName)
    {
        return $this->metaModel->getItemsFor(['table' => $tableName, 'key' => true]);
    }

    /**
     * @return array|null
     */
    public function getOldValues(): array|null
    {
        return $this->oldValues;
    }

    /**
     * General utility function for saving a row in a table.
     *
     * This functions checks for prior existence of the row and switches
     * between insert and update as needed. Key updates can be handled through
     * passing the $oldKeys or by using copyKeys().
     *
     * @see copyKeys()
     *
     * @param $string $table The table to save
     * @param array   $newValues The values to save, including those for other tables
     * @param ?array  $oldKeys The original keys as they where before the changes
     * @return array The values for this table as they were updated
     */
    protected function saveTableData(string $tableName, array $newValues, array $oldKeys = null)
    {
        if (! $newValues) {
            return [];
        }

        $primaryKeys  = $this->getKeysForTable($tableName);
        $primaryCount = count($primaryKeys);
        $filter       = [];

        // \MUtil\EchoOut\EchoOut::r($newValues, $tableName);
        foreach ($primaryKeys as $key) {
            if (array_key_exists($key, $newValues) && (0 == strlen((string) $newValues[$key]))) {
                // Never include null key values, except when we have a save transformer
                if (! $this->metaModel->has($key, MetaModel::SAVE_TRANSFORMER)) {
                    unset($newValues[$key]);
//                    \MUtil\EchoOut\EchoOut::r('Null key value: ' . $key, 'INSERT!!');
                }

            } elseif (isset($oldKeys[$key])) {
//                \MUtil\EchoOut\EchoOut::r($key . ' => ' . $oldKeys[$key], 'Old key');
                $filter[$key] = $oldKeys[$key];
                // Key values left in $returnValues in case of partial key insert

            } else {
                // Check for old key values being stored using copyKeys()
                $copyKey = $this->getKeyCopyName($key);

                if (isset($newValues[$copyKey])) {
                    $filter[$key] = $newValues[$copyKey];
//                    \MUtil\EchoOut\EchoOut::r($key . ' => ' . $newValues[$copyKey], 'Copy key');

                } elseif (isset($newValues[$key])) {
                    $filter[$key] = $newValues[$key];
                }
            }
        }
        if ($filter) {
            $oldValues = $this->sqlRunner->fetchRow(
                $tableName,
                false,
                $this->sqlRunner->createWhere($this->metaModel, $filter),
                []);
            $this->oldValues = array_merge($this->oldValues, $oldValues);
        } else {
            $oldValues = false;
        }


        // Check for actual values for this table to save.
        // \MUtil\EchoOut\EchoOut::track($newValues);
        $saveValues = $this->filterDataForTable($tableName, $newValues, ! $oldValues);
        if ($saveValues) {
//            \MUtil\EchoOut\EchoOut::r($saveValues, 'Return');
            if ($oldValues) {
                // \MUtil\EchoOut\EchoOut::r($filter);
                $save = false;

                // Check for actual changes
                foreach ($oldValues as $name => $value) {
                    // The name is in the set being stored
                    if (array_key_exists($name, $saveValues)) {
                        if ($this->metaModel->isAutoSave($name)) {
                            continue;
                        }

                        if (is_object($saveValues[$name]) || is_object($value)) {
                            $noChange = $saveValues[$name] == $value;
                        } else {
                            // Make sure differences such as extra start zero's on text fields do
                            // not disappear, while preventing a difference between an integer
                            // and string input of triggering a false change
                            $noChange = ($saveValues[$name] == $value) &&
                                (strlen((string)$saveValues[$name]) == strlen((string)$value));
                        }

                        // Detect change that is not auto update
                        if ($noChange) {
                            unset($saveValues[$name]);
                        } else {
                            $save = true;
                        }
                    }
                }
                // Update the row, if the saveMode allows it
                if ($save) {
                    $changed = $this->sqlRunner->updateInTable($tableName, $saveValues, $filter);
                    // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  'changed update ' . $changed . "\n", FILE_APPEND);
                    if ($changed) {
                        $this->addChanged();
                        // Add the old values as we have them and they may be of use later on.
                        $output = $saveValues + $oldValues;

                        // Make sure the copy keys (if any) have the new values as well
                        $output = $this->updateCopyKeys($primaryKeys, $output);
                    } else {
                        $output = $oldValues;
                    }

                    return $output;
                }
                // Add the old values as we have them and they may be of use later on.
                return $saveValues + $oldValues;

            } else {
                // Perform insert
                // \MUtil\EchoOut\EchoOut::r($returnValues);
                $newKeyValues = $this->sqlRunner->insertInTable($tableName, $saveValues);
                $this->addChanged();
                // \MUtil\EchoOut\EchoOut::rs($newKeyValues, $primaryKeys);

                // Composite key returned.
                if (is_array($newKeyValues)) {
                    foreach ($newKeyValues as $key => $value) {
                        $saveValues[$key] = $value;
                    }
                    return $this->updateCopyKeys($primaryKeys, $saveValues);
                }
                // Single key returned
                foreach ($primaryKeys as $key) {
                    // Fill the first empty value
                    if (! isset($saveValues[$key])) {
                        $saveValues[$key] = $newKeyValues;
                        return $this->updateCopyKeys($primaryKeys, $saveValues);
                    }
                }
                // But if all the key values were already filled, make sure the new values are returned.
                return $this->updateCopyKeys($primaryKeys, $saveValues);
            }
        }
        return [];
    }

    protected function updateCopyKeys(array $primaryKeys, array $returnValues)
    {
        foreach ($primaryKeys as $name) {
            $copyKey = $this->getKeyCopyName($name);
            $returnValues[$copyKey] = $returnValues[$name];
        }

        return $returnValues;
    }
}