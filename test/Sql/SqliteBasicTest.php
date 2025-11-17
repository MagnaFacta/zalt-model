<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Laminas\Db\ResultSet\ResultSet;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class SqliteBasicTest extends \PHPUnit\Framework\TestCase
{
    use SqliteUseTrait;

    public function testConnection(): void
    {
        $this->assertInstanceOf(Adapter::class, $this->getAdapter());
    }

    public function testCreation(): void
    {
        $adapter = $this->getAdapter();
        $this->createFillDb($adapter, __DIR__ . '/../data/basicDb');

        $statement = $adapter->query("SELECT * FROM t1");

        $this->assertInstanceOf(Statement::class, $statement);

        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
        $result    = $statement->execute([]);
        $resultSet->initialize($result);

        $this->assertCount(2, $resultSet);
    }
}