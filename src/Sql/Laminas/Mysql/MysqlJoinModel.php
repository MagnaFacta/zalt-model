<?php

declare(strict_types=1);

namespace Zalt\Model\Sql\Laminas\Mysql;

use Zalt\Model\Sql\JoinModel;

class MysqlJoinModel extends JoinModel
{
    use StraightJoinModelTrait;
}