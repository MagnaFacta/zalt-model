<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Traversable;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class JoinTableStore implements \IteratorAggregate
{
    /**
     * @var array table alias => JoinTableItem
     */
    protected array $joins = [];

    public function __construct(protected string $startTableName)
    { }

    /**
     * @param $tableName
     * @param array $joinFields
     * @param string|null $tableAlias
     * @param bool $joinInner
     * @return void
     */
    public function addJoin(string $tableName, array $joinFields, ?string $tableAlias = null, bool $joinInner = true)
    {
        if (! $tableAlias) {
            $tableAlias = $tableName;
        }
        $this->joins[$tableAlias] = new JoinTableItem($tableName, $joinFields, $joinInner, $tableAlias);
    }

    /**
     * @return array table alias => JoinTableItem
     */
    public function getJoins(): array
    {
        return $this->joins;
    }


    public function getStartTableName(): string
    {
        return $this->startTableName;
    }

    public function hasTable(string $tableName): bool
    {
        return (bool) isset($this->joins[$tableName]);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->joins);
    }
}