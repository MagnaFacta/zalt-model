<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql\Laminas;

use Zalt\Model\Sql\JoinCondition;
use Zalt\Model\Sql\JoinTableItem;
use Zalt\Model\Sql\JoinTableStore;
use function intval;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Metadata\Source\Factory;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate\Literal;
use Laminas\Db\Sql\Predicate\Predicate;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\TableGateway\TableGateway;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @since      Class available since version 1.0
 */
class LaminasRunner implements \Zalt\Model\Sql\SqlRunnerInterface
{
    protected string $lastSqlStatement = '';

    protected Sql $sql;

    public function __construct(
        protected Adapter $db
    ) {
        $this->sql = new Sql($this->db);
    }

    /**
     * @inheritDoc
     */
    public function createColumns(MetaModelInterface $metaModel, mixed $columns): mixed
    {
        if (null === $columns) {
            $output = [];
            if ($metaModel->hasItemsUsed()) {
                $output = $metaModel->getItemsUsed();
                foreach ($metaModel->getCol(SqlRunnerInterface::NO_SQL) as $name => $value) {
                    if ($value) {
                        unset($output[$name]);
                    }
                }
            } else {
                $output = [Select::SQL_STAR];
            }
        } elseif (is_array($columns)) {
            $output = $columns;
        } elseif (true == $columns) {
            $output = [Select::SQL_STAR];
        } else {
            $output = [$columns];
        }
        $expressions = $metaModel->getCol('column_expression');
        foreach ($expressions as $name => $expression) {
            if ($expression instanceof Expression) {
                $output[$name] = $expression;
            } else {
                $output[$name] = new Expression($expression);
            }
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function createSort(MetaModelInterface $metaModel, array $sort): mixed
    {
        $output = [];
        foreach ($sort as $field => $type) {
            if (is_string($field) && (str_ends_with(strtoupper($field), ' ASC') || str_ends_with(strtoupper($field), ' DESC'))) {
                // Ignore stated sort if sort is stated in fieldname
                $output[] = $field;
            } else {
                if ($metaModel->has($field)) {
                    $expression = $metaModel->get($field, 'column_expression');
                    if ($expression) {
                        if ($expression instanceof Expression) {
                            $name = $expression;
                        } else {
                            $name = new Expression($expression);
                        }
                    } else {
                        $name = $field;
                    }
                    if (SORT_DESC === $type) {
                        if ($name instanceof Expression) {
                            $output[] = new Expression($name->getExpression() . ' DESC');
                        } else {
                            $output[] = $name . ' DESC';
                        }
                    } else {
                        $output[] = $name;
                    }
                }
            }
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function createWhere(MetaModelInterface $metaModel, mixed $where, $and = true): mixed
    {
        if ($where instanceof Predicate) {
            return $where;
        }
        if (! $where) {
            return [];
        }
        if (is_array($where)) {
            $output = new Where([], $and ? PredicateSet::COMBINED_BY_AND : PredicateSet::COMBINED_BY_OR);
            foreach ($where as $field => $value) {
                if (is_int($field)) {
                    if (is_array($value)) {
                        if ($and) {
                            $output->andPredicate($this->createWhere($metaModel, $value, false));
                        } else {
                            $output->orPredicate($this->createWhere($metaModel, $value, true));
                        }
                    } else {
                        if (is_int($value) && $value != $field) {
                            $output->equalTo(1, 0);
                        }  else {
                            $output->literal('(' . $value . ')');
                        }
                    }
                } else {
                    $expression = $metaModel->get($field, 'column_expression');
                    if ($expression) {
                        if ($expression instanceof Expression) {
                            $name = $expression;
                        } else {
                            $name = new Expression('(' . $expression . ')');
                        }
                    } else {
                        $name = $field;
                    }
                    if ($value instanceof Predicate) {
                        $output->addPredicate($value);
                    } elseif (is_array($value)) {
                        if (1 == count($value)) {
                            if (isset($value[MetaModelInterface::FILTER_CONTAINS])) {
                                $output->like($name, '%' . $value[MetaModelInterface::FILTER_CONTAINS] . '%');
                                continue;
                            }
                            if (isset($value[MetaModelInterface::FILTER_CONTAINS_NOT])) {
                                $output->notLike($name, '%' . $value[MetaModelInterface::FILTER_CONTAINS_NOT] . '%');
                                continue;
                            }
                        }
                        if (2 == count($value)) {
                            if (isset($value[MetaModelInterface::FILTER_BETWEEN_MAX], $value[MetaModelInterface::FILTER_BETWEEN_MIN])) {
                                $output->between($name, $value[MetaModelInterface::FILTER_BETWEEN_MIN], $value[MetaModelInterface::FILTER_BETWEEN_MAX]);
                                continue;
                            }
                        }
                        if (MetaModelInterface::FILTER_NOT == $field) {
                            // Check here as NOT can be part of the main filter
                            $not = new NotPredicate([]);
                            $not->andPredicate($this->createWhere($metaModel, $value, true));
                            $output->addPredicate($not);
                        } elseif ($value) {
                            $output->in($name, $value);
                        } else {
                            // Always false when no values
                            $output->equalTo(1, 0);
                        }
                    } elseif (null === $value) {
                        $output->isNull($name);
                    } else {
                        $output->equalTo($name, $value);
                    }
                }
            }
            return $output;
        }
        return new Literal($where);
    }

    /**
     * @inheritDoc
     */
    public function deleteFromTable(string $tableName, mixed $where) : int
    {
        $table = new TableGateway($tableName, $this->db);
        return $table->delete($where);
    }

    /**
     * @inheritDoc
     */
    public function fetchCount(string|JoinTableStore $tables, mixed $where): int
    {
        $select = $this->getSelect($tables);
        $select->columns(['count' => new Expression("COUNT(*)")]);
        if ($where) {
            $select->where($where);
        }
        $select->limit(1);

        // dump($select->getSqlString($this->db->getPlatform()));
        $rows = $this->fetchRowsFromSelect($select);

        if ($rows) {
            $row = reset($rows);
            if (isset($row['count'])) {
                return intval($row['count']);
            }
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function fetchRow(string|JoinTableStore $tables, mixed $columns, mixed $where, mixed $sort) : array
    {
        $select = $this->getSelect($tables);
        if ($columns) {
            $select->columns($columns, false);
        } else {
            $select->columns([Select::SQL_STAR], false);
        }
        if ($where) {
            $select->where($where);
        }
        if ($sort) {
            $select->order($sort);
        }
        $select->limit(1);

        $rows = $this->fetchRowsFromSelect($select);

        return reset($rows) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function fetchRows(string|JoinTableStore $tables, mixed $columns, mixed $where, mixed $sort, int $offset = null, int $limit = null) : array
    {
        $select = $this->getSelect($tables);

        if ($columns) {
            $select->columns($columns, false);
        } else {
            $select->columns([Select::SQL_STAR], false);
        }
        if ($where) {
            $select->where($where);
        }
        if ($sort) {
            $select->order($sort);
        }

        return $this->fetchRowsFromSelect($select, $offset, $limit);
    }

    /**
     * @inheritDoc
     */
    public function fetchRowsFromSelect(Select $select, int $offset = null, int $limit = null) : array
    {
        if (null !== $offset) {
            $select->offset($offset);
        }
        if (null !== $limit) {
            $select->limit($limit);
        }

        $this->lastSqlStatement = $select->getSqlString($this->db->getPlatform());
        // dump($this->lastSqlStatement);
        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): sql: ' . $this->lastSqlStatement . "\n", FILE_APPEND);
        // echo "SQL: " . $this->lastSqlStatement . "\n";

        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute([]);
        $resultSet->initialize($result);
        return $resultSet->toArray() ?: [];
    }

    public function getLastSqlStatement(): string
    {
        return $this->lastSqlStatement;
    }

    public function getSelect(string|JoinTableStore $tables): Select
    {
        if (is_string($tables)) {
            return $this->sql->select($tables);
        }

        $select = $this->sql->select($tables->getStartTableName());
        foreach ($tables->getJoins() as $join) {
            if ($join instanceof JoinTableItem) {
                $on = [];
                foreach ($join->getJoin() as $key => $value) {
                    if ($value instanceof JoinCondition) {
                        $on[] = $value->getCondition();
                    } else {
                        $on[] = "$key = $value";
                    }
                }
                $where = $this->createWhere($tables->getMetaModel(), $on);

                if ($join->hasAlias()) {
                    $table = [$join->getAlias() => $join->getTable()];
                } else {
                    $table = $join->getTable();
                }

                $select->join($table, $where, [], $join->isInnerJoin() ? Select::JOIN_INNER : Select::JOIN_LEFT);
            }
        }
        return $select;
    }

    /**
     * @inheritDoc
     */
    public function getTableMetaData(string $tableName): array
    {
        $metaData = Factory::createSourceFromAdapter($this->db);

        $fieldData  = [];
        $fieldOrder = [];
        foreach ($metaData->getColumns($tableName) as $column) {
            $name = $column->getName();
            $type = match ($column->getDataType()) {
                'date' => MetaModelInterface::TYPE_DATE,
                'datetime', 'timestamp' => MetaModelInterface::TYPE_DATETIME,
                'time' => MetaModelInterface::TYPE_TIME,
                'int', 'integer', 'mediumint', 'smallint', 'tinyint', 'bigint', 'serial', 'dec', 'decimal', 'double', 'double precision', 'fixed', 'float' => MetaModelInterface::TYPE_NUMERIC,
                default => MetaModelInterface::TYPE_STRING,
            };
            $fieldOrder[$column->getOrdinalPosition()] = $name;

            $fieldData[$name] = [
                'required' => ! $column->isNullable(),
                'table' => $tableName,
                'type' => $type,
            ];

            $length = intval($column->getCharacterMaximumLength());
            if ($length) {
                $fieldData[$name]['maxlength'] = $length;
            } else {
                $decimals = intval($column->getNumericScale());
                if ($decimals) {
                    $fieldData[$name]['decimals'] = $decimals;
                }
                $unsigned = intval($column->getNumericUnsigned());
                if ($unsigned) {
                    $fieldData[$name]['unsigned'] = $unsigned;
                }
                $precision = intval($column->getNumericPrecision());
                if ($precision) {
                    if (! $unsigned) {
                        $precision++;
                    }
                    if ($decimals) {
                        $precision++;
                    }
                    $fieldData[$name]['maxlength'] = $precision;
                }
            }
            $default = $column->getColumnDefault();
            if ($default !== null) {
                switch (strtoupper($default)) {
                    case 'CURRENT_DATE':
                    case 'CURRENT_TIME':
                    case 'CURRENT_TIMESTAMP':
                        $fieldData[$name]['default'] = $default;
                        break;
                    case 'NULL':
                        break;

                    default:
                        $fieldData[$name]['default'] = $default;
                }
            }
        }
        foreach ($metaData->getConstraints($tableName) as $constraint) {
            if ($constraint->isPrimaryKey()) {
                foreach ($constraint->getColumns() as $colName) {
                    $name = $colName;
                    $fieldData[$name]['key'] = true;
                }
            }
//            if ($constraint->isUnique()) {
//
//            }
//            if ($constraint->isForeignKey()) {
//
//            }
        }

        // Supply the data sorted on ordinal position
        ksort($fieldOrder);
        $output = [];
        foreach ($fieldOrder as $name) {
            $output[$name] = $fieldData[$name];
        }

        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($output, true) . "\n", FILE_APPEND);

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function insertInTable(string $tableName, array $values) : ?int
    {
        $table = new TableGateway($tableName, $this->db);

        $table->insert($values);

        return intval($table->getLastInsertValue());
    }

    /**
     * @inheritDoc
     */
    public function updateInTable(string $tableName, array $values, mixed $where) : int
    {
        $table = new TableGateway($tableName, $this->db);

        return $table->update($values, $where);
    }
}