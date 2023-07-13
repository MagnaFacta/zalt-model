<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Zalt\Model\Data\DataReaderTrait;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class SqlTableModel implements FullDataInterface
{
    use DataReaderTrait;
    use SqlModelTrait;
    
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

    public function getName(): string
    {
        return $this->tableName;
    }

    public function hasNew() : bool
    {
        return true;
    }

    public function load($filter = null, $sort = null) : array
    {
        return $this->metaModel->processAfterLoad($this->sqlRunner->fetchRows(
            $this->tableName,
            $this->sqlRunner->createColumns($this->metaModel, null),
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        ));
    }

    public function loadCount($filter = null, $sort = null): int
    {
        $where = $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter));
        return $this->sqlRunner->fetchCount($this->tableName, $where);
    }

    public function loadFirst($filter = null, $sort = null) : array
    {
        return $this->metaModel->processOneRowAfterLoad($this->sqlRunner->fetchRow(
            $this->tableName,
            $this->sqlRunner->createColumns($this->metaModel, true),
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        ));
    }

    public function loadPageWithCount(?int &$total, int $page, int $items, $filter = null, $sort = null): array
    {
        $columns = $this->sqlRunner->createColumns($this->metaModel, true);
        $where   = $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter));
        $order   = $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort));

        $total = $this->sqlRunner->fetchCount($this->tableName, $where);

        return $this->sqlRunner->fetchRows($this->tableName, $columns, $where, $order, ($page - 1) * $items, $items);
    }

    public function save(array $newValues, array $filter = null) : array
    {
        $beforeValues = $this->metaModel->processBeforeSave($newValues);
        $resultValues = $this->saveTableData($this->tableName, $beforeValues, $filter);
        $afterValues  = $this->metaModel->processAfterSave($resultValues);

        if ($this->metaModel->getMeta(MetaModel::LOAD_TRANSFORMER) || $this->metaModel->hasDependencies()) {
            return $this->metaModel->processRowAfterLoad($afterValues, false);
        } else {
            return $afterValues;
        }
    }
}