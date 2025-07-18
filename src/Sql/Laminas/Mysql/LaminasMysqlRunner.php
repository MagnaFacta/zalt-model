<?php

declare(strict_types=1);

namespace Zalt\Model\Sql\Laminas\Mysql;

use Laminas\Db\Adapter\Platform\Mysql;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Zalt\Model\Sql\JoinTableStore;
use Zalt\Model\Sql\Laminas\LaminasRunner;

class LaminasMysqlRunner extends LaminasRunner
{
    /**
     * @inheritDoc
     */
    public function fetchCount(string|JoinTableStore $tables, mixed $where): int
    {
        $columns = ['count' => new Expression("COUNT(*)")];
        $select = $this->getFullSelect($tables, $columns, $where, );

        $rows = $this->fetchExtendedRowsFromSelect($tables, $select);

        if ($rows) {
            $row = reset($rows);
            if (isset($row['count'])) {
                return intval($row['count']);
            }
        }

        return 0;
    }

    /**
     * @inheritDoc
     */
    public function fetchRow(string|JoinTableStore $tables, mixed $columns, mixed $where, mixed $sort) : array
    {
        $select = $this->getFullSelect($tables, $columns, $where, $sort, 1);

        $rows = $this->fetchExtendedRowsFromSelect($tables, $select);

        return reset($rows) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function fetchRows(string|JoinTableStore $tables, mixed $columns, mixed $where, mixed $sort, int $offset = null, int $limit = null) : array
    {
        $select = $this->getFullSelect($tables, $columns, $where, $sort, $limit, $offset);


        return $this->fetchExtendedRowsFromSelect($tables, $select);
    }

    /**
     * @inheritDoc
     */
    public function fetchExtendedRowsFromSelect(string|JoinTableStore $tables, Select $select, int $offset = null, int $limit = null) : array
    {
        $statement = $this->sql->prepareStatementForSqlObject($select);

        $query = $statement->getSql();

        $statement->setSql($this->transformQuery($query, $tables, $select));

        $this->lastSqlStatement = $statement->getSql();

        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
        $result    = $statement->execute([]);
        $resultSet->initialize($result);
        return $resultSet->toArray() ?: [];
    }

    public function getFullSelect(string|JoinTableStore $tables, array|null $columns = null, $where = null, array|Expression|string|null $sort = null, int|null $limit = null, int|null $offset = null): Select
    {
        $select = $this->getSelect($tables);
        if ($columns) {
            $select->columns($columns, false);
        } else {
            $select->columns([Select::SQL_STAR], false);
        }
        if ($where) {
            $select->where($where);
        }
        if ($sort) {
            $select->order($sort);
        }

        if (null !== $offset) {
            $select->offset($offset);
        }
        if (null !== $limit) {
            $select->limit($limit);
        }

        return $select;
    }

    public function isMysql(): bool
    {
        return $this->db->getPlatform() instanceof Mysql;
    }

    public function transformQuery(string $query, string|JoinTableStore $tables, Select $select): string
    {
        if (!$this->isMysql()) {
            return $query;
        }
        $joins = array_values($tables->getJoins());

        $innerCount = 0;
        foreach($joins as $join) {
            if ($join->isInnerJoin()) {
                $innerCount++;
                if ($join->isStraight()) {
                    $query = $this->replaceNthOccurrence($query, 'INNER JOIN', 'STRAIGHT_JOIN', $innerCount);
                }
            }
        }

        return $query;
    }

    protected function replaceNthOccurrence(string $haystack, string $needle, string $replacement, int $n = 0): string
    {
        $position = -1;
        $string = strtolower($haystack);
        $needle = strtolower($needle);
        for ($i = 0; $i < $n; $i++) {
            $position = strpos($string, $needle, $position + 1);
            if ($position === false) {
                return $haystack;
            }
        }

        $test = substr_replace($haystack, $replacement, $position, strlen($needle));

        return $test;
    }
}