<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Data
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Data;

use Zalt\Late\Late;
use Zalt\Late\RepeatableInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Exception\ModelException;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Data
 * @since      Class available since version 1.0
 */
trait DataReaderTrait
{
    protected array $filter = [];

    protected array $sort = [];

    protected function checkFilter(mixed $filter): array
    {
        if (null === $filter) {
            return $this->metaModel->processFilter($this->getFilter());
        }
        if (is_array($filter)) {
            return $this->metaModel->processFilter($filter);
        }
        if ($filter) {
            return $this->metaModel->processFilter([$filter]);
        }

        return [];
    }

    protected function checkSort(mixed $sort): array
    {
        if (null === $sort) {
            $sort = $this->getSort();
        } elseif (! is_array($sort)) {
            $sort = [$sort => SORT_ASC];
        }
        $sort = $this->metaModel->processSort($sort);

        $output = [];
        foreach ($sort as $field => $value) {
            if (SORT_ASC === $value || SORT_DESC === $value) {
                $output[$field] = $value;
            } else {
                $output[$value] = SORT_ASC;
            }
        }
        return $output;
    }

    public function getFilter(): array
    {
        return $this->filter;
    }

    public function getBridgeFor($identifier, ...$args): BridgeInterface
    {
        $modelLoader = $this->metaModel->getMetaModelLoader();
        return $modelLoader->createBridge($identifier, $this, ...$args);
    }

    public function getMetaModel(): MetaModelInterface
    {
        return $this->metaModel;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    public function hasFilter() : bool
    {
        return (bool) $this->filter;
    }

    public function hasSort() : bool
    {
        return (bool) $this->sort;
    }

    public function loadFirst($filter = null, $sort = null, $columns = null) : array
    {
        $rows = $this->load($filter, $sort, $columns);
        if (! $rows) {
            return [];
        }
        return reset($rows);
    }

    public function loadNew() : array
    {
        return $this->metaModel->processOneRowAfterLoad($this->loadNewRaw(), true, false);
    }

    protected function loadNewRaw()
    {
        return $this->metaModel->getCol('default') +
            array_fill_keys($this->metaModel->getItemNames(), null);
    }

    /**
     * Processes and returns an array of post data
     *
     * @param array $postData
     * @param boolean $create
     * @param mixed $filter Null to use the stored filter, array to specify a different filter
     * @param mixed $sort Null to use the stored sort, array to specify a different sort
     * @return array
     */
    public function loadPostData(array $postData, $create = false, $filter = null, $sort = null, $columns = null): array
    {
        if (! $this instanceof FullDataInterface) {
            throw new ModelException(
                sprintf('Function "%s" may not be used for class "%s" as it does not implement "%s".', __FUNCTION__, get_class($this), FullDataInterface::class)
            );
        }

        if ($create) {
            $modelData = $this->loadNewRaw();
        } else {
            $modelData = $this->loadFirst($filter, $sort, $columns);
        }
        if ($postData && $modelData) {
            // Elements that do not occur in post data when empty
            // while they should contain an empty array
            $excludes = array_fill_keys(array_merge(
                $this->metaModel->getItemsFor('elementClass', 'MultiCheckbox'),
                $this->metaModel->getItemsFor('elementClass', 'MultiSelect')
            ), []);
        } else {
            $excludes = [];
        }

        // 1 - When posting, posted data is used as a value first
        // 2 - Then we use any values already set
        return $this->metaModel->processOneRowAfterLoad($postData + $excludes + $modelData, $create, true);
    }

    public function loadRepeatable($filter = null, $sort = null, $columns = null) : ?RepeatableInterface
    {
        $rows = $this->load($filter, $sort, $columns);
        if ($rows) {
            return Late::repeat($rows);
        }
        return null;
    }

    public function setFilter(array $filter) : DataReaderInterface
    {
        $this->filter = $filter;
        return $this;
    }

    public function setSort(array $sort) : DataReaderInterface
    {
        $this->sort = $sort;
        return $this;
    }
}