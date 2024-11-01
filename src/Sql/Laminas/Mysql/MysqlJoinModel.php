<?php

declare(strict_types=1);

namespace Zalt\Model\Sql\Laminas;

use Zalt\Model\Sql\JoinModel;

class MysqlJoinModel extends JoinModel
{
    public function startJoin($startTableName, bool $saveable = true): JoinModel
    {
        foreach ($this->sqlRunner->getTableMetaData($startTableName) as $name => $settings) {
            $this->metaModel->set($name, $settings);
        }
        $this->joinStore = new StraightJoinTableStore($startTableName, $this->metaModel);
        if ($saveable) {
            $this->saveTables = [$startTableName => $startTableName];
        } else {
            $this->saveTables = [];
        }
        $this->metaModel->setKeys($this->getKeysForTable($startTableName));

        return $this;
    }
}