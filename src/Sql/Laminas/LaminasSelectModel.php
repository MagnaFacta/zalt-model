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
use Zalt\Late\RepeatableInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @since      Class available since version 1.0
 */
class LaminasSelectModel implements DataReaderInterface
{
    protected array $filter = [];

    protected array $sort = [];

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

    /**
     * Create the bridge for the specific idenitifier
     *
     * This will always be a new bridge because otherwise you get
     * instabilities as bridge objects are shared without knowledge
     *
     * @param string $identifier
     * @param array $args Optional first of extra arguments
     * @return \Zalt\Model\Bridge\BridgeInterface
     */
    public function getBridgeFor($identifier, ...$args): BridgeInterface
    {
        return $this->metaModel->getBridgeForModel($this, $identifier, ...$args);
    }

    /**
     * @inheritDoc
     */
    public function getFilter(): array
    {
        return $this->filter;
    }

    public function getMetaModel(): MetaModelInterface
    {
        return $this->metaModel;
    }
    
    protected function getSelectFor($filter, $sort)
    {
        $select = clone $this->select;
        
        if (null === $filter) {
            $filter = $this->getFilter();
        }
        if ($filter) {
            // file_put_contents('data/logs/echo.txt', __CLASS__ . '->' . __FUNCTION__ . '(' . __LINE__ . '): ' .  print_r($filter, true) . "\n", FILE_APPEND);
            $select->where($this->laminasRunner->createWhere($this->metaModel, $filter));
        }

        if (null === $sort) {
            $sort = $this->getSort();
        }
        $sorts = [];
        foreach ($sort as $field => $value) {
            if (SORT_ASC === $value) {
                $sorts[] = $field;
            } elseif (SORT_DESC === $value) {
                $sorts[] = $field . ' DESC';
            } else {
                $sorts[] = $value;
            }
        }
        if ($sorts) {
            $select->order($sorts);
        }
        
        return $select;
    }
    
    /**
     * @inheritDoc
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * @inheritDoc
     */
    public function hasFilter() : bool
    {
        return (bool) $this->filter;
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
    public function hasSort() : bool
    {
        return (bool) $this->sort;
    }

    /**
     * @inheritDoc
     */
    public function load($filter = null, $sort = null) : array
    {
        $select = $this->getSelectFor($filter, $sort);
        return $this->laminasRunner->fetchRowsFromSelect($select);
    }

    /**
     * @inheritDoc
     */
    public function loadFirst($filter = null, $sort = null) : array
    {
        // TODO: Implement loadFirst() method.
    }

    /**
     * @inheritDoc
     */
    public function loadNew() : array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function loadRepeatable($filter = true, $sort = true) : ?RepeatableInterface
    {
        // TODO: Implement loadRepeatable() method.
    }
    
    /**
     * @inheritDoc
     */
    public function setFilter(array $filter) : DataReaderInterface
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setSort(array $sort) : DataReaderInterface
    {
        $this->sort = $sort;
        return $this;
    }
}