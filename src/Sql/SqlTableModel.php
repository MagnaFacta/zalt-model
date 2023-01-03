<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataReaderTrait;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\MetaModelException;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class SqlTableModel implements DataReaderInterface, FullDataInterface
{
    use DataReaderTrait;
    use SqlModelTrait;
    
    protected int $saveMode = SqlRunnerInterface::SAVE_MODE_ALL;
    
    protected string $tableName;
    
    public function __construct(
        protected MetaModelInterface $metaModel,
        protected SqlRunnerInterface $sqlRunner 
    ) { 
        $this->tableName = $this->metaModel->getName();
        
        foreach ($this->sqlRunner->getTableMetaData($this->tableName) as $name => $settings) {
            $this->metaModel->set($name, $settings);
        }
        // Set the keys
        $this->metaModel->getKeys();
   }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = null): int
    {
        return $this->sqlRunner->deleteFromTable($this->tableName, $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)));
    }

    public function hasNew() : bool
    {
        return true;
    }

    public function load($filter = null, $sort = null) : array
    {
        return $this->metaModel->processAfterLoad($this->sqlRunner->fetchRowsFromTable(
            $this->tableName,
            $this->sqlRunner->createColumns($this->metaModel, null),
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        ));
    }

    public function loadFirst($filter = null, $sort = null) : array
    {
        return $this->metaModel->processOneRowAfterLoad($this->sqlRunner->fetchRowFromTable(
            $this->tableName,
            $this->sqlRunner->createColumns($this->metaModel, true),
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        ));
    }

    public function loadPostData(array $postData, $create = false, $filter = true, $sort = true)
    {
        if ($create) {
            $modelData = $this->loadNewRaw();
        } else {
            $modelData = $this->sqlRunner->fetchRowFromTable(
                $this->tableName,
                $this->sqlRunner->createColumns($this->metaModel, true),
                $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
                $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
            );
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

    public function save(array $newValues, array $filter = null) : array
    {
        $beforeValues = $this->metaModel->processBeforeSave($newValues);
        $resultValues = $this->saveTableData($this->tableName, $beforeValues,$filter, $this->saveMode);
        $afterValues  = $this->metaModel->processAfterSave($resultValues);

        if ($this->metaModel->getMeta(MetaModel::LOAD_TRANSFORMER) || $this->metaModel->hasDependencies()) {
            return $this->metaModel->processRowAfterLoad($afterValues, false);
        } else {
            return $afterValues;
        }
    }
}