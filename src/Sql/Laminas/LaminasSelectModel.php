<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql\Laminas;

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
        protected LaminasRunner $laminasRunner,
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

    /**
     * @inheritDoc
     */
    public function load($filter = null, $sort = null) : array
    {
        $select = $this->getSelectFor($filter, $sort);
        return $this->metaModel->processAfterLoad($this->laminasRunner->fetchRowsFromSelect($select));
    }

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
}