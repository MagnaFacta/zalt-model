<?php

namespace Zalt\Model\Transform;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;

class OneToManyTransformer extends NestedTransformer
{
    public function __construct(
        DataReaderInterface $subModel,
        array $joinFields,
        string|null $name = null
    )
    {
        $this->addModel($subModel, $joinFields, $name);
    }

    /**
     * Do not return field info, as it is not relevant for the parent model
     *
     * @param MetaModelInterface $model
     * @return array
     */
    public function getFieldInfo(MetaModelInterface $model)
    {
        return [];
    }

    /**
     * Function to allow overruling of transform for certain models
     *
     * @param MetaModelInterface $model Parent model
     * @param DataReaderInterface $sub Sub model
     * @param array $data The nested data rows
     * @param array $join The join array
     * @param string $name Name of sub model
     * @param boolean $new True when loading a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     */
    protected function transformLoadSubModel(MetaModelInterface $model, DataReaderInterface $sub, array &$data, array $join, $name, $new, $isPostData)
    {
        $newRow = [];
        if ($new) {
            $newRow = $sub->loadNew();
        }

        $joinFilter = [];
        $joinIndex = [];
        foreach ($data as $key => $row) {
            // E.g. if loaded from a post
            if (isset($row[$name])) {
                $processedRow = $sub->getMetaModel()->processAfterLoad($row[$name], $new, $isPostData);
                $data[$key][$name] = $processedRow;
                continue;
            }

            if ($new) {
                $data[$key][$name] = $newRow;
                continue;
            }

            $joinKeyParts = [];
            foreach ($join as $parent => $child) {
                if (isset($row[$parent])) {
                    $joinFilter[$child][] = $row[$parent];
                    $joinKeyParts[] = $row[$parent];
                }
            }
            if (count($joinKeyParts)) {
                $joinIndex[join('::', $joinKeyParts)] = $key;
            } else {
                $data[$key][$name] = $newRow;
            }
        }

        if (count($joinFilter)) {
            $rows = $sub->load($joinFilter);

            foreach($rows as $row) {
                $joinKeyParts = [];
                foreach ($join as $child) {
                    $joinKeyParts[] = $row[$child];
                }
                $joinKey = join('::', $joinKeyParts);
                if (isset($joinIndex[$joinKey])) {
                    $data[$joinIndex[$joinKey]][$name][] = $row;
                }
            }
        }

        return $data;
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
        string $name)
    {
        if ($this->skipSave) {
            return;
        }

        if (! isset($row[$name])) {
            return;
        }

        $subItems = $row[$name];
        $keys = [];

        // Get the parent key values.
        foreach ($join as $parent => $child) {
            if (isset($row[$parent])) {
                $keys[$child] = $row[$parent];
            } else {
                // if there is no parent identifier set, don't save
                return;
            }
        }

        $saved = [];
        foreach($subItems as $key => $subrow) {
            // Make sure the (possibly changed) parent key
            // is stored in the sub data.
            $subItems[$key] = $keys + $subrow;
            $saved[$key] = $sub->save($subItems[$key]);
        }

        $oldResults = $sub->load($keys);
        $subKeys = $sub->getMetaModel()->getKeys();
        $deletedValues = $this->findDeletedItems($oldResults, $subItems, $subKeys);

        foreach($deletedValues as $deletedValue) {
            $sub->delete($deletedValue);
        }

        $row[$name] = $saved;
    }

    protected function findDeletedItems(array $oldValues, array $newValues, array $keysToCheck): array
    {
        $oldValues = $this->extractAndSortKeyPairs($oldValues, $keysToCheck);
        $newValues = $this->extractAndSortKeyPairs($newValues, $keysToCheck);

        $serializedOldValues = array_map('serialize', $oldValues);
        $serializedNewValues = array_map('serialize', $newValues);

        $missingItems = array_diff($serializedOldValues, $serializedNewValues);

        return array_map('unserialize', $missingItems);
    }

    protected function extractAndSortKeyPairs($array, $keys) {
        return array_map(function($item) use ($keys) {
            // Extract only the relevant keys
            $filteredItem = array_intersect_key($item, array_flip($keys));
            // Sort keys to ensure consistent order
            ksort($filteredItem);
            return $filteredItem;
        }, $array);
    }
}