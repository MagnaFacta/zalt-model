<?php

declare(strict_types=1);

namespace Zalt\Model\Sql\Laminas\Mysql;

use Zalt\Model\Sql\JoinTableItem;

class StraightJoinTableItem extends JoinTableItem
{
    public function __construct(
        protected readonly string $joinTable,
        protected array $joinFields,
        protected readonly bool $joinInner = true,
        protected readonly ?string $tableAlias = null,
        protected readonly bool $straight = false,
    )
    {
        parent::__construct($this->joinTable, $this->joinFields, $this->joinInner, $this->tableAlias);
    }

    /**
     * @return bool
     */
    public function isStraight(): bool
    {
        return $this->straight;
    }
}