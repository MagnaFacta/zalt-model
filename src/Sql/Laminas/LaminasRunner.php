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
use Laminas\Db\ResultSet\ResultSet;
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
                            } else {
                                $output->equalTo($name, reset($value));
                            }
                            continue;
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

    public function fetchRowFromTable(string $tableName, mixed $where) : array
    {
        // TODO: Implement fetchRowFromTable() method.
    }

    public function fetchRowsFromSelect(Select $select) : array
    {
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute([]);
        $resultSet->initialize($result);
        return $resultSet->toArray() ?: [];
    }

    public function fetchRowsFromTable(string $tableName, mixed $where) : array
    {
        // TODO: Implement fetchRowsFromTable() method.
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