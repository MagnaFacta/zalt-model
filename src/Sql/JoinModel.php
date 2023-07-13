<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use phpDocumentor\Reflection\Types\Boolean;
use Zalt\Model\Data\DataReaderTrait;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\ModelException;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class JoinModel implements FullDataInterface
{
    use DataReaderTrait;
    use SqlModelTrait;

    protected JoinTableStore $joinStore;

    protected array $saveTables = [];

    public function __construct(
        protected MetaModelInterface $metaModel,
        protected SqlRunnerInterface $sqlRunner
    )
    { }

    /**
     * Check the passed saveTable information and return 'new style' SAVE_MODE
     * constant array
     *
     * @param array $saveTables Optional array containing the table names to save,
     * otherwise the tables set to save at model level will be saved.
     * @return array Containing savetable data
     */
    protected function _checkSaveTables(array $saveTables = null): array
    {
        if (null === $saveTables) {
            return $this->saveTables;
        }

        $results = array();
        foreach ((array) $saveTables as $tableName) {
            if (isset($this->saveTables[$tableName])) {
                $results[$tableName] = $tableName;
            } else {
                $key = array_search($tableName, $this->saveTables);
                if ($key) {
                    $results[$key] = $tableName;
                }
            }
        }
        return $results;
    }

    public function addLeftTable(string $tableName, array $joinFields, bool $saveable = true, ?string $tableAlias = null): JoinModel
    {
        return $this->addTable($tableName, $joinFields, $saveable, $tableAlias, false);
    }

    public function addTable(string $tableName, array $joinFields, bool $saveable = true, ?string $tableAlias = null, bool $joinInner = true): JoinModel
    {
        $joinStore = $this->getJoinStore();

        if ($tableAlias) {
            $prefix = $tableAlias . '.';
            if ($joinStore->hasTable($tableAlias)) {
                throw new ModelException("Table alias $tableAlias already added to join.");
            }
        } else {
            $prefix = '';
            if ($joinStore->hasTable($tableName)) {
                throw new ModelException("Table $tableName already added to join.");
            }
        }

        // First get meta-data for table
        $settings = $this->sqlRunner->getTableMetaData($tableName);

        // Set the joins
        $realJoins   = [];
        foreach ($joinFields as $from => $to) {
            $condition = new JoinCondition();
            if ($from && (! is_int($from))) {
                $field = $condition->setLeftField($from);
                if (! $field->isExpression()) {
                    if ($this->metaModel->has($from)) {
                        if (! $field->hasTableName()) {
                            $field->setTableName($this->metaModel->get($from, 'table'));
                        }
                    } elseif (isset($settings[$from])) {
                        if ($tableAlias) {
                            $field->setAliasName($tableAlias);
                        }
                        if (! $field->hasTableName()) {
                            $field->setTableName($tableName);
                        }
                    }
                }
            }
            if ($to) {
                $field = $condition->setRightField($to);
                if (! $field->isExpression()) {
                    if (isset($settings[$to])) {
                        if ($tableAlias) {
                            $field->setAliasName($tableAlias);
                        }
                        if (! $field->hasTableName()) {
                            $field->setTableName($tableName);
                        }
                    } elseif ($this->metaModel->has($to)) {
                        if (! $field->hasTableName()) {
                            $field->setTableName($this->metaModel->get($to, 'table'));
                        }
                    }
                }
            }

            $realJoins[$from] = $condition;
        }

        // Add settings to metamodel
        $targetTable = $prefix ?? $tableName . '.';
        foreach ($settings as $name => $settings) {
            $settings['table'] = $prefix . $tableName;
            $this->metaModel->set($prefix . $name, $settings);
        }

        $joinStore->addJoin($tableName, $realJoins, $tableAlias, $joinInner);
        if ($saveable) {
            $this->saveTables[$tableAlias] = $tableName;
        }

        return $this;
    }

    public function delete($filter = null): int
    {
        // TODO: Implement delete() method.
        return 0;
    }

    public function getJoinStore(): JoinTableStore
    {
        if (! isset($this->joinStore)) {
            // Use startJoin first when the MetaModel name is not a table
            $this->startJoin($this->metaModel->getName());
        }

        return $this->joinStore;
    }

    public function getName(): string
    {
        return $this->metaModel->getName();
    }

    public function hasNew(): bool
    {
        return (bool) $this->saveTables;
    }

    public function load($filter = null, $sort = null): array
    {
        return $this->sqlRunner->fetchRows(
            $this->getJoinStore(),
            $this->sqlRunner->createColumns($this->metaModel, true),
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        );
    }

    public function loadCount($filter = null, $sort = null): int
    {
        $where = $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter));
        return $this->sqlRunner->fetchCount($this->getJoinStore(), $where);
    }

    public function loadPageWithCount(?int &$total, int $page, int $items, $filter = null, $sort = null): array
    {
        $joins   = $this->getJoinStore();
        $columns = $this->sqlRunner->createColumns($this->metaModel, true);
        $where   = $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter));
        $order   = $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort));

        $total = $this->sqlRunner->fetchCount($joins, $where);

        return $this->sqlRunner->fetchRows($joins, $columns, $where, $order, ($page - 1) * $items, $items);
    }

    public function save(array $newValues, array $filter = null, array $saveTables = null): array
    {
        $saveTables = $this->_checkSaveTables($saveTables);

        $oldValues = $newValues;
        foreach ($saveTables as $tableAlias => $tableName) {
            // Gotta repeat this every time, as keys may be set later
//            foreach ($this->joinStore as $join) {
//                // Use is_string as $target and $target can be e.g. a \Zend_Db_Expr() object
//                // as $source is an index keys it must be a string
//                if (is_string($target)) {
//                    if (! (isset($newValues[$target]) && $newValues[$target])) {
//                        if (! (isset($newValues[$source]) && $newValues[$source])) {
//                            if (\MUtil\Model::$verbose) {
//                                \MUtil\EchoOut\EchoOut::r('Missing: ' . $source . ' -> ' . $target, 'ERROR!');
//                            }
//                            continue;
//                        }
//                        $newValues[$target] = $newValues[$source];
//
//                    } elseif (! (isset($newValues[$source]) && $newValues[$source])) {
//                        $newValues[$source] = $newValues[$target];
//
//                    } elseif ((strlen($newValues[$target]) > 0) &&
//                        (strlen($newValues[$source]) > 0) &&
//                        $newValues[$target] != $newValues[$source]) {
//                        // Join key values changed.
//                        //
//                        // Set the old values as the filter
//                        $filter[$target] = $newValues[$target];
//                        $filter[$source] = $newValues[$source];
//
//                        // Switch the target value to the value in the source field.
//                        //
//                        // JOIN FIELD ORDER IS IMPORTANT!!!
//                        // The changing field must be stated first in the join statement.
//                        $newValues[$target] = $newValues[$source];
//                    }
//                } elseif ($target instanceof \Zend_Db_Expr &&
//                    (! (isset($newValues[$source]) && $newValues[$source]))) {
//                    $newValues[$source] = $target;
//                }
//            }
//
//            //$this->_saveTableData returns the new row values, including any automatic changes.
//            $newValues = $this->_saveTableData($this->_tables[$tableName], $newValues, $filter, $saveMode)
//                + $oldValues;
//            // \MUtil\EchoOut\EchoOut::track($oldValues, $newValues, $filter);
//            $oldValues = $newValues;
        }

        // If anything has changed, it counts as only one item for the user.
//        if ($this->getChanged() > $oldChanged) {
//            $this->setChanged(++$oldChanged);
//        }
//
//        return $newValues;
        // TODO: Implement save() method.
        return $newValues;
    }

    public function startJoin($startTableName, bool $saveable = true): JoinModel
    {
        foreach ($this->sqlRunner->getTableMetaData($startTableName) as $name => $settings) {
            $this->metaModel->set($name, $settings);
        }
        $this->joinStore = new JoinTableStore($startTableName, $this->metaModel);
        if ($saveable) {
            $this->saveTables = [$startTableName => $startTableName];
        } else {
            $this->saveTables = [];
        }

        return $this;
    }
}