<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql\Laminas;

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

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @since      Class available since version 1.0
 */
class LaminasRunner implements \Zalt\Model\Sql\SqlRunnerInterface
{
    protected Sql $sql;
    
    public function __construct(
        protected Adapter $db
    ) { 
        $this->sql = new Sql($this->db);
    }


    public function checkSelect(mixed $select) : bool
    {
        // TODO: Implement checkSelect() method.
    }

    public function createSort(MetaModelInterface $metaModel, array $sort): mixed
    {
        $output = [];
        foreach ($sort as $field => $type) {
            if (is_string($field) && (str_ends_with(strtoupper($field), ' ASC') || str_ends_with(strtoupper($field), ' DESC'))) {
                // Ignore stated sort if sort is stated in fieldname
                $output[] = $field;
            } else {
                if ($metaModel->has($field)) {
                    if (SORT_DESC === $type) {
                        $output[] = $field . ' DESC';
                    } else {
                        $output[] = $field;
                    }
                }
            }
        }

        return $output;
    }

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
                            $output->literal($value);
                        }
                    }
                } else {
                    $expression = $metaModel->get($field, 'column_expression');
                    if ($expression) {
                        $name = '(' . $expression . ')';
                    } else {
                        $name = $field;
                    }
                    if (is_array($value)) {
                        if (1 == count($value)) {
                            if (isset($value[MetaModelInterface::FILTER_CONTAINS])) {
                                $output->like($name, '%' . $value['like'] . '%');
                                continue;
                            }
                        }
                        if (2 == count($value)) {
                            if (isset($value[MetaModelInterface::FILTER_BETWEEN_MAX], $value[MetaModelInterface::FILTER_BETWEEN_MIN])) {
                                $output->between($name, $value[MetaModelInterface::FILTER_BETWEEN_MIN], $value[MetaModelInterface::FILTER_BETWEEN_MAX]);
                                continue;
                            }
                        }
                        if ($value) {
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

    public function deleteFromTable(string $tableName, mixed $where) : int
    {
        $table = new TableGateway($tableName, $this->db);
        return $table->delete($where);
    }

    public function fetchRowFromTable(string $tableName, mixed $where, mixed $sort) : array
    {
        $select = $this->sql->select($tableName);
        $select->where($where);
        $select->order($sort);
        $select->limit(1);

        return $this->fetchRowsFromSelect($select);
    }

    public function fetchRowsFromSelect(Select $select) : array
    {
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute([]);
        $resultSet->initialize($result);
        return $resultSet->toArray() ?: [];
    }

    public function fetchRowsFromTable(string $tableName, mixed $where, mixed $sort) : array
    {
        $select = $this->sql->select($tableName);
        $select->where($where);
        $select->order($sort);
        
        return $this->fetchRowsFromSelect($select); 
    }

    /**
     * @param string      $tableName
     * @param string|null $alias
     * @return array name => settings for metamodel
     */
    public function getTableMetaData(string $tableName, string $alias = null): array
    {
        $metaData = Factory::createSourceFromAdapter($this->db);

        if ((null === $alias) || ($alias == $tableName)) {
            $aliasPrefix = '';
            $alias       = $tableName;
        } else {
            $aliasPrefix = $alias . '.';
        }
        
        $fieldData  = [];
        $fieldOrder = [];
        foreach ($metaData->getColumns($tableName) as $column) {
            $name = $aliasPrefix . $column->getName();
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
                'table' => $alias,
                'type' => $type,
            ];

            $length = $column->getCharacterMaximumLength();
            if ($length) {
                $fieldData[$name]['maxlength'] = $length;
            } else {
                $decimals = $column->getNumericScale();
                if ($decimals) {
                    $fieldData[$name]['decimals'] = $decimals;
                }
                $unsigned = $column->getNumericUnsigned();
                if ($unsigned) {
                    $fieldData[$name]['unsigned'] = $unsigned;
                }
                $precision = $column->getNumericPrecision();
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
            if ($default) {
                switch (strtoupper($default)) {
                    case 'CURRENT_DATE':
                    case 'CURRENT_TIME':
                    case 'CURRENT_TIMESTAMP':
                        $fieldData[$name]['default'] = new Expression($default);
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
                    $name = $aliasPrefix . $colName;
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

    public function insertInTable(string $tableName, array $values) : ?int
    {
        $table = new TableGateway($tableName, $this->db);
        
        $table->insert($values);
        
        return $table->getLastInsertValue();
    }

    public function updateInTable(string $tableName, array $values, mixed $where) : int
    {
        $table = new TableGateway($tableName, $this->db);
        
        $table->update($values, $where);
    }
}