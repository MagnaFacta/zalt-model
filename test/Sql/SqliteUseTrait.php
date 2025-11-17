<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Symfony\Component\Yaml\Yaml;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
trait SqliteUseTrait
{
    public function createDb(Adapter $adapter, string $sqlScriptFile)
    {
        $sqlScript = file_get_contents($sqlScriptFile);

        foreach (explode(';', $sqlScript) as $sqlCommand) {
            if (trim($sqlCommand)) {
                // echo $sqlCommand . "\n";
                $stmt = $adapter->query($sqlCommand);
                $stmt->execute();
            }
        }
    }

    public function createFillDb(Adapter $adapter, string $fileName): Adapter
    {
        $this->createDb($adapter, $fileName . ".sql");

        $dataFile = $fileName . ".yml";
        if (file_exists($dataFile)) {
            $this->fillDbYaml($adapter, $dataFile);
        }

        return $adapter;
    }

    public function fillDbYaml(Adapter $adapter, string $yamlFile)
    {
        $this->insertData($adapter, Yaml::parseFile($yamlFile));
    }


    protected function getAdapter(): Adapter
    {
        $testConfig = [
            'driver' => 'Pdo_Sqlite',
            'dbname' => ':memory:',
            'username' => 'test',
        ];

        return new Adapter($testConfig);
    }

    protected function insertData(Adapter $adapter, array $data)
    {
        foreach ($data as $tableName => $rows) {
            $tableGateway = new TableGateway($tableName, $adapter);
            foreach ($rows as $row) {
                $tableGateway->insert($row);
            }
        }
    }
}