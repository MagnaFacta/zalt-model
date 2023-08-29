<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Transform
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Transform;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Transform
 * @since      Class available since version 1.0
 */
class ToColumnChildTransformer extends ToManyTransformer
{
    public function __construct(
        protected string $singleColumn,
        $savable = false)
    {
        parent::__construct($savable);
    }

    protected function transformLoadSubModel(
        MetaModelInterface $model,
        DataReaderInterface $sub,
        array &$data,
        array $join,
        string $name,
        bool $new,
        bool $isPostData)
    {
        $parent = array_key_first($join);
        $child = $join[$parent];

        $filter = [];
        $parentIds = array_column($data, $parent);

        foreach ($data as $key => $row) {

            $rows = null;
            // E.g. if loaded from a post
            if (isset($row[$name])) {
                $rows = $sub->getMetaModel()->processAfterLoad([$row[$name]], $new, $isPostData);
                unset($parentIds[$key]);
            } elseif ($new) {
                $rows = $sub->loadNew();
                unset($parentIds[$key]);
            }

            if ($rows !== null && isset($rows[$child])) {
                $data[$key][$name] = $rows[$child];
            }
        }

        $parentIndexes = array_flip($parentIds);
        if ($parentIds) {
            $filter[$child] = $parentIds;
        }

        $combinedResult = $sub->load($filter);

        foreach($combinedResult as $key => $result) {
            if (isset($result[$child]) && isset($parentIndexes[$result[$child]])) {
                $data[$parentIndexes[$result[$child]]][$name][] = $result[$this->singleColumn];
            }
        }
    }

    protected function transformSaveSubModel(
        MetaModelInterface $model,
        DataWriterInterface $sub,
        array &$row,
        array $join,
        string $name)
    {
        if (!$this->savable) {
            return;
        }

        if (! isset($row[$name])) {
            return;
        }

        $data = $row[$name];

        $child = reset($join);
        $parent = key($join);

        $parentId = $row[$parent];
        $filter = [$child => $parentId];
        $oldResults = $sub->load($filter);

        $newResults = [];
        $insertResults = [];
        $deletedResults = [];

        foreach($oldResults as $oldResult) {
            $index = array_search($oldResult[$this->singleColumn], $data);
            if ($index !== false) {
                $newResults[] = $oldResult;
                unset($data[$index]);
            } else {
                $deletedResults[] = $oldResult;
            }
        }

        foreach($data as $newValue) {
            $insertResults[] = [
                $child => $parentId,
                $this->singleColumn => $newValue,
            ];
        }

        if (!empty($insertResults)) {
            foreach ($insertResults as $result) {
                $insertedResults[] = $sub->save($result);
            }
            $newResults = array_merge($newResults, $insertedResults);
        }

        $keys = $sub->getMetaModel()->getKeys();
        $key = reset($keys);

        $deleteIds = array_column($deletedResults, $key);
        if (!empty($deleteIds)) {
            foreach($deleteIds as $deleteId) {
                $sub->delete([$key => $deleteId]);
            }
        }

        $row[$name] = $newResults;
    }
}