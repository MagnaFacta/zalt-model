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
            return $this->getFilter();
        }
        if (is_array($filter)) {
            return $filter;
        }        
        if ($filter) {
            return [$filter];
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
    
    public function loadFirst($filter = null, $sort = null) : array
    {
        $rows = $this->load($filter, $sort);
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

    public function loadRepeatable($filter = null, $sort = null) : ?RepeatableInterface
    {
        $rows = $this->load($filter, $sort);
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