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
                if ($to instanceof JoinFieldPart) {
                    $to = $field->getNameInModel();
                }
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

    public function load($filter = null, $sort = null, $columns = null): array
    {
        return $this->sqlRunner->fetchRows(
            $this->getJoinStore(),
            $this->sqlRunner->createColumns($this->metaModel, $columns),
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        );
    }

    public function loadCount($filter = null, $sort = null): int
    {
        $where = $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter));
        return $this->sqlRunner->fetchCount($this->getJoinStore(), $where);
    }

    public function loadPageWithCount(?int &$total, int $page, int $items, $filter = null, $sort = null, $columns = null): array
    {
        $joins   = $this->getJoinStore();
        $columns = $this->sqlRunner->createColumns($this->metaModel, $columns);
        $where   = $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter));
        $order   = $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort));

        $total = $this->sqlRunner->fetchCount($joins, $where);

        return $this->sqlRunner->fetchRows($joins, $columns, $where, $order, ($page - 1) * $items, $items);
    }

    public function save(array $newValues, array $filter = null, array $saveTables = null): array
    {
        $oldChanged    = $this->changed;

        $saveTables    = $this->_checkSaveTables($saveTables);
        $oldValues     = $newValues;
        $resultValues  = $this->metaModel->processBeforeSave($newValues);
        $fieldMappings = $this->joinStore->getFieldMappings();

        // print_r($fieldMappings);
        foreach ($saveTables as $tableAlias => $tableName) {
            // First copy all required keys
            foreach ($fieldMappings as $to => $source) {
                if (isset($resultValues[$source])) {
                    $resultValues[$to] = $resultValues[$source];
                }
            }

            // This will not work with aliased values
            $resultValues = $this->saveTableData($tableName, $resultValues, $newValues) + $resultValues;
            $oldValues    = $resultValues;
        }
        $afterValues  = $this->metaModel->processAfterSave($resultValues);

        // If anything has changed, it counts as only one item for the user.
        if ($this->changed > $oldChanged) {
            $this->changed = $oldChanged + 1;
        }

        return $afterValues;
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