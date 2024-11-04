<?php

namespace Zalt\Model\Sql\Laminas\Mysql;

use Zalt\Model\Exception\ModelException;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\JoinCondition;
use Zalt\Model\Sql\JoinFieldPart;
use Zalt\Model\Sql\SqlRunnerInterface;

trait StraightJoinModelTrait
{
    protected MetaModelInterface $metaModel;

    protected SqlRunnerInterface $sqlRunner;

    /**
     * Add a joined table
     *
     * @param string $tableName
     * @param array $joinFields Preferably in the form existing table field => new table field, though expression may also be used on the right side
     * @param bool $saveable When true data in the table may be updated or deleted
     * @param string|null $tableAlias If a table alias exists you cannot save the table data
     * @param bool $joinInner When false, a left join is used (purposely we do not use right and full outer joins
     * @return $this
     * @throws ModelException
     */
    public function addTable(string $tableName, array $joinFields, bool $saveable = false, ?string $tableAlias = null, bool $joinInner = true, bool $straightJoin  = false): self
    {
        if (!$this->sqlRunner instanceof LaminasMysqlRunner) {
            throw new ModelException('Using Straight Join also requires the \Zalt\Model\Sql\Laminas\Mysql\LaminasMysqlRunner');
        }

        $joinStore = $this->getJoinStore();

        if ($tableAlias) {
            $prefix = $tableAlias . '.';
            if ($joinStore->hasTable($tableAlias)) {
                throw new ModelException("Table alias $tableAlias already added to join.");
            }
            // Saving currently does not work with table aliases
            $saveable = false;
            $this->joinFields[$tableAlias] = $joinFields;
        } else {
            $prefix = '';
            if ($joinStore->hasTable($tableName)) {
                throw new ModelException("Table $tableName already added to join.");
            }
            $this->joinFields[$tableName] = $joinFields;
        }

        // First get meta-data for table
        $tableMetaData = $this->sqlRunner->getTableMetaData($tableName);

        // Set the joins
        $realJoins = [];
        foreach ($joinFields as $from => $to) {
            $condition = new JoinCondition();
            if ($from && (!is_int($from))) {
                $field = $condition->setLeftField($from);
                if (!$field->isExpression()) {
                    if ($this->metaModel->has($from)) {
                        if (!$field->hasTableName()) {
                            $field->setTableName($this->metaModel->get($from, 'table'));
                        }
                    } elseif (isset($tableMetaData[$from])) {
                        if ($tableAlias) {
                            $field->setAliasName($tableAlias);
                        }
                        if (!$field->hasTableName()) {
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
                if (!$field->isExpression()) {
                    if (isset($tableMetaData[$to])) {
                        if ($tableAlias) {
                            $field->setAliasName($tableAlias);
                        }
                        if (!$field->hasTableName()) {
                            $field->setTableName($tableName);
                        }
                    } elseif ($this->metaModel->has($to)) {
                        if (!$field->hasTableName()) {
                            $field->setTableName($this->metaModel->get($to, 'table'));
                        }
                    }
                }
            }

            $realJoins[$from] = $condition;
        }

        // Add settings to metamodel
        $targetTable = strlen($prefix) ? $prefix : $tableName . '.';
        foreach ($tableMetaData as $name => $settings) {
            $settings['table'] = $prefix . $tableName;
            $this->metaModel->set($prefix . $name, $settings);
        }

        $joinStore->addJoin($tableName, $realJoins, $tableAlias, $joinInner, $straightJoin);
        if ($saveable) {
            $this->saveTables[$tableAlias ?? $tableName] = $tableName;
        }

        return $this;
    }
    public function addStraightTable(string $tableName, array $joinFields, bool $saveable = false, ?string $tableAlias = null, bool $joinInner = true): self
    {
        return $this->addTable($tableName, $joinFields, $saveable, $tableAlias, $joinInner, true);
    }

    public function getJoinStore(): StraightJoinTableStore
    {
        /**
         * @var StraightJoinTableStore
         */
        return parent::getJoinStore();
    }

    public function startJoin($startTableName, bool $saveable = true): self
    {
        foreach ($this->sqlRunner->getTableMetaData($startTableName) as $name => $settings) {
            $this->metaModel->set($name, $settings);
        }
        $this->joinStore = new StraightJoinTableStore($startTableName, $this->metaModel);
        if ($saveable) {
            $this->saveTables = [$startTableName => $startTableName];
        } else {
            $this->saveTables = [];
        }
        $this->metaModel->setKeys($this->getKeysForTable($startTableName));

        return $this;
    }
}