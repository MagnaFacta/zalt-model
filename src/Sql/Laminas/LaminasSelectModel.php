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

    protected ?string $text = null;
    
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
    public function getTextFilter(): ?string
    {
        return $this->text;
    }

    /**
     * @inheritDoc
     */
    public function getTextSearchFilter($searchText)
    {
        // TODO: Implement getTextSearchFilter() method.
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
    public function hasTextSearchFilter() : bool
    {
        return (bool) $this->text; 
    }

    /**
     * @inheritDoc
     */
    public function load($filter = null, $sort = null) : array
    {
        return $this->laminasRunner->fetchRowsFromSelect($this->select);
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
        // TODO: Implement loadNew() method.
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

    /**
     * @inheritDoc
     */
    public function setTextFilter(?string $text) : DataReaderInterface
    {
        $this->text = $text;
        return $this;
    }
}