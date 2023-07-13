<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class JoinTableItem
{
    public function __construct(
        protected readonly string $joinTable,
        protected array $joinFields,
        protected readonly bool $joinInner = true,
        protected readonly ?string $tableAlias = null,
    )
    {
        foreach ($this->joinFields as $from => $to) {
            if (! $to instanceof JoinCondition) {
                $condition = new JoinCondition();
                $condition->setLeftField($from);
                $condition->setRightField($to);

                $this->joinFields[$to] = $condition;
            }
        }
    }

    public function getAlias(): ?string
    {
        return $this->tableAlias;
    }

    public function getJoin(): array
    {
        return $this->joinFields;
    }

    public function getTable(): string
    {
        return $this->joinTable;
    }

    public function hasAlias(): bool
    {
        return null !== $this->tableAlias;
    }

    public function isInnerJoin(): bool
    {
        return $this->joinInner;
    }
}