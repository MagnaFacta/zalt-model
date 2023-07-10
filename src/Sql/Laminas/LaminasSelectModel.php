<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql\Laminas;

use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Platform\Mysql\Mysql;
use Laminas\Db\Sql\Select;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataReaderTrait;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @since      Class available since version 1.0
 */
class LaminasSelectModel implements DataReaderInterface
{
    use DataReaderTrait;
    
    /**
     * @param \Laminas\Db\Sql\Select                $select
     * @param \Zalt\Model\MetaModelInterface        $metaModel
     * @param \Zalt\Model\Sql\Laminas\LaminasRunner $laminasRunner
     */
    public function __construct(
        protected Select $select,
        protected MetaModelInterface $metaModel,
        protected LaminasRunner $laminasRunner
    )
    { }

    protected function getSelectFor($filter, $sort)
    {
        $select = clone $this->select;
        
        // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($filter, true) . "\n", FILE_APPEND);
        $columns = $select->getRawState(Select::COLUMNS);
        $select->columns($this->laminasRunner->createColumns($this->metaModel, $columns ?: null));
        $select->where($this->laminasRunner->createWhere($this->metaModel, $this->checkFilter($filter)));
        $select->order($this->laminasRunner->createSort($this->metaModel, $this->checkSort($sort)));
        
        return $select;
    }
    
    /**
     * @inheritDoc
     */
    public function hasNew() : bool
    {
        return false;
    }

    public function getName(): string
    {
        return $this->metaModel->getName();
    }

    /**
     * @inheritDoc
     */
    public function load($filter = null, $sort = null) : array
    {
        $select = $this->getSelectFor($filter, $sort);
        return $this->metaModel->processAfterLoad($this->laminasRunner->fetchRowsFromSelect($select));
    }

    public function loadCount($filter = null, $sort = null): int
    {
        $where   = $this->laminasRunner->createWhere($this->metaModel, $this->checkFilter($filter));

        $selectCount = clone $this->select;
        $selectCount->columns(['count' => new Expression("COUNT(*)")]);
        $selectCount->where($where);

        $rows = $this->laminasRunner->fetchRowsFromSelect($selectCount);
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
    public function loadFirst($filter = null, $sort = null) : array
    {
        $select = $this->getSelectFor($filter, $sort);
        $select->limit(1);
        $rows = $this->laminasRunner->fetchRowsFromSelect($select);
        if ($rows) {
            return $this->metaModel->processOneRowAfterLoad(reset($rows));
        }
        
        return [];
    }

    /**
     * @inheritDoc
     */
    public function loadPageWithCount(?int &$total, int $page, int $items, $filter = null, $sort = null): array
    {
        $columns = $this->laminasRunner->createColumns($this->metaModel, true);
        $where   = $this->laminasRunner->createWhere($this->metaModel, $this->checkFilter($filter));
        $order   = $this->laminasRunner->createSort($this->metaModel, $this->checkSort($sort));

        $selectCount = clone $this->select;
        $selectCount->columns(['count' => new Expression("COUNT(*)")]);
        $selectCount->where($where);

        $total = 0;
        $rows = $this->laminasRunner->fetchRowsFromSelect($selectCount);
        if ($rows) {
            $row = reset($rows);
            if (isset($row['count'])) {
                $total = intval($row['count']);
            }
        }

        $selectRows  = clone $this->select;
        $selectRows->columns($columns);
        $selectRows->where($where);
        $selectRows->order($order);
        $output = $this->laminasRunner->fetchRowsFromSelect($selectRows, ($page - 1) * $items, $items);

        return $this->metaModel->processAfterLoad($output);
    }
}