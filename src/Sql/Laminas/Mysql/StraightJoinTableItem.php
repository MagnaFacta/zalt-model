<?php

declare(strict_types=1);

namespace Zalt\Model\Sql\Laminas;

use Zalt\Model\Sql\JoinTableItem;

class StraightJoinTableItem extends JoinTableItem
{
    public function __construct(
        string $joinTable,
        array $joinFields,
        bool $joinInner = true,
        ?string $tableAlias = null,
        protected readonly bool $straight = false,
    )
    {
        parent::__construct($joinTable, $joinFields, $joinInner, $tableAlias);
    }
}