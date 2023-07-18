<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Traversable;
use Zalt\Model\MetaModelInterface;

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

    public function __construct(
        protected string $startTableName,
        protected MetaModelInterface $metaModel,
    )
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
     * @return array Mapping field to copy value to => field to copy value from
     */
    public function getFieldMappings(): array
    {
        $output = [];

        $currentTables[$this->startTableName] = $this->startTableName;
        foreach ($this->joins as $join) {
            /**
             * @var JoinTableItem $join
             */
            $join->getFieldMappings($currentTables, $output);
        }
        return $output;
    }

    /**
     * @return array table alias => JoinTableItem
     */
    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getMetaModel(): MetaModelInterface
    {
        return $this->metaModel;
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