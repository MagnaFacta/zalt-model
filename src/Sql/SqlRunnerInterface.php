<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
interface SqlRunnerInterface
{
    /**
     * Constant for nat an SQL columns
     */
    const NO_SQL = 'noSql';

    /**
     * Default save mode: execute all saves
     */
    const SAVE_MODE_ALL    = 7;

    /**
     * Allow deletes to be executed
     */
    const SAVE_MODE_DELETE = 4;

    /**
     * Allow inserts to be executed
     */
    const SAVE_MODE_INSERT = 2;

    /**
     * Allow updates to be executed
     */
    const SAVE_MODE_UPDATE = 1;

    /**
     * Do nothing
     */
    const SAVE_MODE_NONE   = 0;

    /**
     * @param \Zalt\Model\MetaModelInterface $metaModel
     * @param array                          $sort
     * @return mixed Something to be used as a sort
     */
    public function createColumns(MetaModelInterface $metaModel, mixed $columns): mixed;

    /**
     * @param \Zalt\Model\MetaModelInterface $metaModel
     * @param array                          $sort
     * @return mixed Something to be used as a sort
     */
    public function createSort(MetaModelInterface $metaModel, array $sort): mixed;
    
    /**
     * Check a filter and make sure it works for the SQL version
     * @param MetaModelInterface $metaModel
     * @param mixed $where
     * @return mixed Something to be used as a where
     */
    public function createWhere(MetaModelInterface $metaModel, mixed $where): mixed;
    
    /**
     * @param string $tableName
     * @param mixed  $where
     * @return int The number of rows deleted
     */
    public function deleteFromTable(string $tableName, mixed $where): int;

    /**
     * @param string $tableName
     * @param mixed  $where
     * @param mixed  $sort
     * @return array One row of data
     */
    public function fetchCountFromTable(string $tableName, mixed $where): int;

    /**
     * @param string $tableName
     * @param mixed  $columns
     * @param mixed  $where
     * @param mixed  $sort
     * @return array One row of data
     */
    public function fetchRowFromTable(string $tableName, mixed $columns, mixed $where, mixed $sort): array;

    /**
     * @param string $tableName
     * @param mixed  $columns
     * @param mixed  $where
     * @param mixed  $sort
     * @return array Nested rows of data
     */
    public function fetchRowsFromTable(string $tableName, mixed $columns, mixed $where, mixed $sort, int $offset = null, int $limit = null): array;

    /**
     * @param string      $tableName
     * @param string|null $alias
     * @return array name => [settings] for metamodel
     */
    public function getTableMetaData(string $tableName, string $alias = null): array;
 
     /**
     * @param string $tableName
     * @param array  $values
     * @return ?int null or last autogenerated insert id
     */
    public function insertInTable(string $tableName, array $values): ?int;

    /**
     * @param string $tableName
     * @param array  $values
     * @param mixed  $where
     * @return int The number of rows updated
     */
    public function updateInTable(string $tableName, array $values, mixed $where): int;
}