<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Db
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

/**
 *
 * @package    Zalt
 * @subpackage Model\Db
 * @since      Class available since version 1.0
 */
interface ModelSqlInterface
{
    /**
     * @param string $tableName
     * @return array Nested array name => [settings]
     */
    public function getTableMetaInfo(string $tableName): array;
}