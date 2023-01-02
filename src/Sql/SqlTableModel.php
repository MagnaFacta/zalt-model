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

    public function hasNew() : bool
    {
        return true;
    }

    public function load($filter = null, $sort = null) : array
    {
        return $this->sqlRunner->fetchRowsFromTable(
            $this->tableName,
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        );
    }

    public function loadFirst($filter = null, $sort = null) : array
    {
        return $this->sqlRunner->fetchRowFromTable(
            $this->tableName,
            $this->sqlRunner->createWhere($this->metaModel, $this->checkFilter($filter)),
            $this->sqlRunner->createSort($this->metaModel, $this->checkSort($sort))
        );
    }

    public function loadNew() : array
    {
        // TODO: Implement loadNew() method.
    }

    public function loadPostData(array $postData, $create = false, $filter = true, $sort = true)
    {
        // TODO: Implement loadPostData() method.
    }

    public function save(array $newValues, array $filter = null) : array
    {
        // TODO: Implement save() method.
    }
}