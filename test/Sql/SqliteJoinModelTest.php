<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Sql\Laminas\LaminasRunner;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class SqliteJoinModelTest extends \PHPUnit\Framework\TestCase
{
    use MetaModelTestTrait;
    use SqliteUseTrait;

    public function getModel(?string $table): JoinModel
    {
        $adapter = $this->getAdapter();
        $this->createFillDb($adapter, __DIR__ . '/../data/joinDb');

        $sm = $this->getServiceManager();
        $sm->set(SqlRunnerInterface::class, new LaminasRunner($adapter));
        $modelLoader = $this->getModelLoader();

        return $modelLoader->createModel(JoinModel::class, $table);
    }

//    public function testLoadExpressionJoinedTables()
//    {
//        $model = $this->getModel('family');
//        $model->addTable('companies', ['cwork' => 'cid', 'companies.name LIKE "Company%"']);
//
//        $this->assertCount(3, $model->load());
//    }

    public function testLoadInnerJoinedTables()
    {
        $model = $this->getModel('family');
        $model->addTable('companies', ['cwork' => 'cid']);

        $this->assertCount(7, $model->load());

    }

    public function testLoadLeftJoinedTables()
    {
        $model = $this->getModel('family');
        $model->addLeftTable('companies', ['cwork' => 'cid']);

        $this->assertCount(10, $model->load());
        $this->assertCount(3, $model->load(['cwork' => 2]));

        $items = 4;
        $total = 0;
        $this->assertCount($items, $model->loadPageWithCount($total, 2, $items));
        $this->assertEquals(10, $total);
    }

    public function testLoadSelfJoinedTables()
    {
        $model = $this->getModel('family');
        $model->addLeftTable('family', ['fparent1' => 'fid'], false, 'parent1');
        $model->addLeftTable('family', ['fparent2' => 'parent2.fid'], false, 'parent2');

        $this->assertCount(10, $model->load());
        $this->assertCount(1, $model->load(['parent1.fid' => 100]));
    }

    public function testLoadSelfJoinedTables2()
    {
        $model = $this->getModel('family');
        $model->addLeftTable('family', ['fid' => 'fparent1'], false, 'child1');
        $model->addLeftTable('family', ['child2.fid' => 'family.fid'], false, 'child2');

        $this->assertCount(12, $model->load());
        $this->assertCount(1, $model->load(['child1.fid' => 400]));
    }

    public function testLoadSingleTable()
    {
        $model = $this->getModel('family');

        $this->assertInstanceOf(JoinModel::class, $model);
        $this->assertInstanceOf(FullDataInterface::class, $model);
        $this->assertCount(10, $model->load());

        $this->assertEquals(4, $model->loadCount([['fparent1' => 301, 'fparent2' => 301]]));
    }
}