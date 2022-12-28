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
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\TableGateway;

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
        file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($select->getRawState(), true) . "\n", FILE_APPEND);
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