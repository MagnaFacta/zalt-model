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
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Transform
 * @since      Class available since version 1.0
 */
class ToManyTransformer extends NestedTransformer
{
    public function __construct(
        protected bool $savable = false)
    { }

    public function transformFilterSubModel(
        MetaModelInterface $model,
        DataReaderInterface $sub,
        array $filter,
        array $join,
    )
    {
        $itemNames = $sub->getMetaModel()->getItemNames();
        $subFilter = array_intersect(array_keys($filter), $itemNames);

        $child = reset($join);
        $parent = key($join);

//        if (isset($filter[\MUtil\Model::TEXT_FILTER])) {
//            $subFilter += $sub->getTextSearchFilter($filter[\MUtil\Model::TEXT_FILTER]);
//            $mainFilter = $model->getTextSearchFilter($filter[\Mutil_model::TEXT_FILTER]);
//
//        }

        if (count($subFilter)) {
            $results = $sub->load($subFilter);
            if ($results) {
                $subFilterValues = array_column($results, $child);
                $addFilter = ' OR ' . $parent . ' IN ('.join(',', $subFilterValues).')';
//                unset($filter[\MUtil\Model::TEXT_FILTER]);
//                foreach($mainFilter as $mainFilterSub) {
//                    $filter[] = $mainFilterSub . $addFilter;
//                }
            }
        }


        return $filter;
    }

    protected function transformLoadSubModel(
        MetaModelInterface $model,
        DataReaderInterface $sub,
        array &$data,
        array $join,
        string $name,
        bool $new,
        bool $isPostData,
    )
    {
        $child = reset($join);
        $parent = key($join);

        $filter = [];
        $parentIds = array_column($data, $parent);

        foreach ($data as $key => $row) {

            $rows = null;
            // E.g. if loaded from a post
            if (isset($row[$name])) {
                $rows = $sub->getMetaModel()->processAfterLoad($row[$name], $new, $isPostData);
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
        $filter[$child] = $parentIds;

        $combinedResult = $sub->load($filter);

        foreach($combinedResult as $key => $result) {
            if (isset($result[$child]) && isset($parentIndexes[$result[$child]])) {
                $data[$parentIndexes[$result[$child]]][$name][] = $result;
            }
        }
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MetaModelInterface $model
     * @param FullDataInterface $sub
     * @param array $row
     * @param array $join
     * @param string $name
     */
    protected function transformSaveSubModel(
        MetaModelInterface $model,
        FullDataInterface $sub,
        array &$row,
        array $join,
        string $name,
    )
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

        $deletedResults = [];

        $keys = $sub->getMetaModel()->getKeys();
        $key  = array_key_first($keys);

        $dataKeys = array_column($data, $key);

        foreach($oldResults as $oldResult) {
            $index = array_search($oldResult[$key], $dataKeys);
            if ($index !== false) {
                $saveRows[] = $oldResult;
                unset($data[$index]);
            } else {
                $deletedResults[] = $oldResult;
            }
        }

        foreach($data as $newValue) {
            $newValue[$child] = $parentId;
            $saveRows[] = $newValue;
        }

        $newResults = [];
        if (!empty($saveRows)) {
            foreach ($saveRows as $saveRow) {
                $newResults[] = $sub->save($saveRow);
            }
        }

        $deleteIds = array_column($deletedResults, $key);
        if (!empty($deleteIds)) {
            $sub->delete([$key => $deleteIds]);
        }

        $row[$name] = $newResults;
    }

}