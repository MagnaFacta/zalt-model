<?php

declare(strict_types=1);

namespace Zalt\Model\Sql\Laminas;

use Zalt\Model\Sql\JoinTableStore;

class StraightJoinTableStore extends JoinTableStore
{
    /**
     * @param $tableName
     * @param array $joinFields
     * @param string|null $tableAlias
     * @param bool $joinInner
     * @return void
     */
    public function addJoin(string $tableName, array $joinFields, ?string $tableAlias = null, bool $joinInner = true, bool $straight = false): void
    {
        if (! $tableAlias) {
            $tableAlias = $tableName;
        }
        $this->joins[$tableAlias] = new StraightJoinTableItem($tableName, $joinFields, $joinInner, $tableAlias, $straight);
    }
}