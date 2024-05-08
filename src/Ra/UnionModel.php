<?php

namespace Zalt\Model\Ra;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataReaderTrait;
use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\ModelException;
use Zalt\Model\MetaModelInterface;

class UnionModel implements FullDataInterface
{
    use DataReaderTrait;
    use SortArrayTrait;

    protected int $changed = 0;

    protected array $clearableKeys = [];

    protected array|null $oldValues = null;
    protected array $unionMapsFrom = [];

    protected array $unionMapsTo = [];
    protected array $unionModels = [];

    public function __construct(
        protected MetaModelInterface $metaModel,
        protected string $modelField = 'sub',
    )
    {
        $this->metaModel->set($this->modelField, [
            'elementClass' => 'Hidden',
        ]);
    }

    public function addUnionModel(DataReaderInterface $model, array $fieldMap = null, $name = null): self
    {
        if (null === $name) {
            $name = $model->getName();
        }

        $this->unionModels[$name] = $model;

        if ($fieldMap) {
            $this->unionMapsFrom[$name] = $fieldMap;
            $this->unionMapsTo[$name]   = array_flip($fieldMap);
        } else {
            $this->unionMapsFrom[$name] = null;
            $this->unionMapsTo[$name]   = null;
            $fieldMap = [];
        }

        $subMetaModel = $model->getMetaModel();

        foreach ($subMetaModel->getItemsOrdered() as $subName) {
            $mainName = $fieldMap[$subName] ?? $subName;
            $this->metaModel->set($mainName, $subMetaModel->get($subName));
        }

        return $this;
    }

    /**
     * Gets the keys that should be cleared when moving a field from one submodel to another
     *
     * @param array|null $row An optional row, this allows submodels to specify the clearable keys per row
     * @return array name => name
     */
    public function getClearableKeys(array|null $row = null): array
    {
        return $this->clearableKeys;
    }

    protected function getFilterModels(array $filter): array
    {
        if (isset($filter[$this->modelField])) {
            $name = $filter[$this->modelField];
            unset($filter[$this->modelField]);
            return array($name => $this->getUnionModel($name));
        }

        return $this->getUnionModels();
    }

    /**
     * Get the name of the union model that should be used for this row.
     *
     * Not overruling this role means that the content of the row can never
     * result in a switch from one sub-model to another sub-model.
     *
     * Also you will have to handle the setting of the correct model manually
     *
     * @param array $row
     * @return string|null
     */
    public function getModelNameForRow(array $row): string|null
    {
        if (isset($row[$this->modelField])) {
            return $row[$this->modelField];
        }
        throw new ModelException('Union model name not found in row');
    }

    public function getUnionModel(string $name): DataReaderInterface
    {
        return $this->unionModels[$name];
    }

    public function getUnionModels(): array
    {
        return $this->unionModels;
    }

    public function hasNew(): bool
    {
        // All sub models must allow new rows
        foreach ($this->unionModels as $model) {
            if (! ($model instanceof DataReaderInterface && $model->hasNew())) {
                return false;
            }
        }

        return true;
    }

    public function load($filter = null, $sort = null, $columns = null): array
    {
        $filter = $this->checkFilter($filter);
        $sort = $this->checkSort($sort);

        $setCount = 0;
        $results  = [];

        foreach ($this->getFilterModels($filter) as $name => $model) {
            $modelFilter = $this->map($filter, $name, false, true);

            if (isset($this->_unionMapsTo[$name]) && $this->_unionMapsTo[$name]) {
                // Translate the texts filters
                foreach ($modelFilter as $key => $value) {
                    if (is_numeric($key) && is_string($value)) {
                        $modelFilter[$key] = strtr($value, $this->_unionMapsTo[$name]);
                    }
                }
            }
            $mappedSort = $sort;
            if ($mappedSort !== null) {
                $mappedSort = $this->map($sort, $name, false, false);
            }

            $resultSet = $model->load($modelFilter, $mappedSort);

            if ($resultSet) {
                $sub = array($this->modelField => $name);
                foreach ($resultSet as $row) {
                    $results[] = $sub + $this->map($row, $name, true, false);
                }
                $setCount = $setCount + 1;
            }
        }

        if ($setCount && $sort) {
            $results = $this->sortData($results, $sort);
        }

        if (is_array($results)) {
            $results = $this->metaModel->processAfterLoad($results);
        }

        return $results;
    }

    public function loadCount($filter = null): int
    {
        $count = 0;
        foreach ($this->getFilterModels($filter) as $model) {
            $count += $model->loadCount($filter);
        }

        return $count;
    }

    public function loadPage(int $page, int $items, $filter = null, $sort = null, $columns = null): array
    {
        $output = $this->load($filter, $sort);

        return array_slice($output, ($page - 1) * $items, $items);
    }

    public function loadPageWithCount(
        ?int &$total,
        int $page,
        int $items,
        $filter = null,
        $sort = null,
        $columns = null
    ): array {
        $output = $this->load($filter, $sort);
        $total  = count($output);

        return array_slice($output, ($page - 1) * $items, $items);
    }

    /**
     * Map the fields in a row of values from|to a sub model
     *
     * @param array $row The row of values to map
     * @param string $name Union sub model name
     * @param bool $from When true map from the fields names in the sub model to the fields names of this model
     * @param bool $recursive When true sub arrays are mapped as well (only used for filter renaming)
     * @return array
     */
    protected function map(array|null $row, string $name, bool $from = true, bool$recursive = false): array
    {
        if ($from) {
            $mapStore = $this->unionMapsFrom;
        } else {
            $mapStore = $this->unionMapsTo;
        }

        if (! (isset($mapStore[$name]) && $mapStore[$name])) {
            return $row;
        }

        return $this->translateRowFields($row, $mapStore[$name], $recursive);
    }

    /**
     * Maps the key names in the array from the current name to the new
     * name in the $mapArray
     *
     * @param array $sourceArray The array to replace the key names in
     * @param array $mapArray array containing current name => new name
     * @param bool $recursive When true sub arrays are also mapped
     * @return array
     */
    protected function translateRowFields(array $sourceArray, array $mapArray, bool $recursive = false): array
    {
        $result = [];
        foreach ($sourceArray as $name => $value) {

            if ($recursive && is_array($value)) {
                $value = $this->translateRowFields($value, $mapArray, true);
            }

            if (isset($mapArray[$name])) {
                $result[$mapArray[$name]] = $value;
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    public function save(array $newValues, array $filter = null): array
    {
        $newValues = $this->metaModel->processRowBeforeSave($newValues);

        $newName = $this->getModelNameForRow($newValues);

        if (isset($filter[$this->modelField])) {
            $oldName = $filter[$this->modelField];
        } elseif (isset($newValues[$this->modelField])) {
            $oldName = $newValues[$this->modelField];
        } else {
            $oldName = false;
        }

        if ($oldName && ($oldName != $newName)) {
            $model     = $this->getUnionModel($oldName);
            $unionMetaModel = $model->getMetaModel();
            $modelKeys = $this->map($unionMetaModel->getKeys(), $oldName, false, false);

            // Make sure both the names and the keys are in the keys of the array
            $modelKeys    = $modelKeys + array_combine($modelKeys, $modelKeys);
            $deleteFilter = array_intersect_key($this->map($newValues, $oldName, false, false), $modelKeys);

            if ($deleteFilter && $model instanceof FullDataInterface) {
                $model->delete($deleteFilter);

                $cleanup = $this->getClearableKeys($newValues);
                if ($cleanup) {
                    // Make sure both the names and the keys are in the keys of the array
                    $newValues = array_diff_key($newValues, $cleanup, array_combine($cleanup, $cleanup));
                }
            }
        }
        if ($newName) {
            $model  = $this->getUnionModel($newName);
            if ($model instanceof DataWriterInterface) {
                $result = $model->save(
                    $this->map($newValues, $newName, false, false),
                    $this->map((array)$filter, $newName, false, true)
                );

                $this->changed += $model->getChanged();
                $this->oldValues = $model->getOldValues();
                return array($this->modelField => $newName) + $this->map($result, $newName, true, false);
            }
        }

        throw new ModelException('Could not save to union model as values do not belong to a sub model.');
    }

    public function delete($filter = null): int
    {
        $filter  = $this->checkFilter($filter);
        $deleted = 0;
        foreach ($this->getFilterModels($filter) as $name => $model) {
            if ($model instanceof FullDataInterface) {
                $deleted = $deleted + $model->delete($this->map($filter, $name, false, true));
            }
        }
        return $deleted;
    }

    public function getName(): string
    {
        return $this->metaModel->getName();
    }

    public function setClearableKeys(array $keys = null): void
    {
        if ($keys === null) {
            $keys = $this->metaModel->getKeys();
        }
        $this->clearableKeys = $keys;
    }

    public function getChanged(): int
    {
        return $this->changed;
    }

    protected function addChanged(): void
    {
        $this->changed++;
    }

    protected function resetChanged(): void
    {
        $this->changed = 0;
    }

    /**
     * @return array|null
     */
    public function getOldValues(): array|null
    {
        return $this->oldValues;
    }
}