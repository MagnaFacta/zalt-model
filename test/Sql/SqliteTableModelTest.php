<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Laminas\Db\Adapter\Adapter;
use MUtil\Model\TableModel;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Sql\Laminas\LaminasRunner;
use Zalt\Model\Sql\Laminas\LaminasRunnerFactory;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class SqliteTableModelTest extends \PHPUnit\Framework\TestCase
{
    use MetaModelTestTrait;
    use SqliteUseTrait;

    public function getModel(?string $table): SqlTableModel
    {
        $adapter = $this->getAdapter();
        $this->createFillDb($adapter, __DIR__ . '/../data/basicDb');

        $sm = $this->getServiceManager();
        $sm->set(Adapter::class, $adapter);

        $factory = new LaminasRunnerFactory();
        $sm->set(SqlRunnerInterface::class, $factory($sm));
        $modelLoader = $this->getModelLoader();

        // @phpstan-ignore return.type
        return $modelLoader->createModel(SqlTableModel::class, $table);
    }

    public function testDelete(): void
    {
        $model = $this->getModel('t1');

        $this->assertEquals(0, $model->delete(['id' => 200]));
        $this->assertEquals(1, $model->delete(['id' => 2]));
        $this->assertEquals(1, $model->loadCount());
    }

    public function testEditNoTracking()
    {
        $model = $this->getModel('t1');
        $row1 = $model->loadFirst(null, ['id desc' => SORT_ASC]);

        $metaModel = $model->getMetaModel();
        $this->assertFalse($metaModel->hasItemsUsed());

        $model->copyKeys();

        $row2 = $model->loadFirst(null, ['id desc']);

        $this->assertCount(3, $row1);
        $this->assertCount(4, $row2);
    }

    public function testEditWithTracking()
    {
        $model = $this->getModel('t1');
        $row1 = $model->loadFirst(null, ['id desc' => SORT_ASC]);

        $metaModel = $model->getMetaModel();
        $this->assertFalse($metaModel->hasItemsUsed());

        $metaModel->trackUsage();
        $fields = ['id', 'c1', 'c2', 'nonExistingColumn'];
        $metaModel->setMulti($fields, ['label' => 'Text']);
        $metaModel->set('nonExistingColumn', [SqlRunnerInterface::NO_SQL => true]);
        foreach ($fields as $field) {
            $metaModel->get($field, 'label');
        }
        $this->assertTrue($metaModel->hasItemsUsed());
        $model->copyKeys();

        $row2 = $model->loadFirst(null, ['__c_1_3_copy__id__key_k_0_p_1__' => SORT_DESC]);

        $this->assertCount(3, $row1);
        $this->assertCount(4, $row2);
    }

    public function testInsert(): void
    {
        $model = $this->getModel('t1');

        $this->assertInstanceOf(SqlTableModel::class, $model);
        $this->assertInstanceOf(FullDataInterface::class, $model);
        $this->assertTrue($model->hasNew());

        $model->save(['id' => 1, 'c1' => 'col1-1']);
        $this->assertEquals(0, $model->getChanged());

        $model->save(['id' => 1, 'c1' => 'col1-1b']);
        $this->assertEquals(1, $model->getChanged());

        $this->assertCount(2, $model->load());

        $model->save(['c1' => 'col1-3', 'c2' => 'col2-3']);
        $this->assertEquals(2, $model->getChanged());
        $this->assertCount(3, $model->load());
    }

    public function testLoad(): void
    {
        $model = $this->getModel('t1');

        $row2 = ['id' => 2, 'c1' => 'col1-2'];

        $this->assertEquals(2, $model->loadCount());
        $this->assertEquals($row2, $model->loadFirst(['id' => 2], ['id' => SORT_DESC], ['id', 'c1']));
        $row2['c2'] = 'col2-2';
        $this->assertEquals($row2, $model->loadFirst(['id' => 2], ['id' => SORT_ASC], true));

        $this->assertEquals(['c2' => $row2['c2']], $model->loadFirst(['id' => 2], columns: ['c2']));

        $total = 0;
        $rows = $model->load();
        $this->assertEquals($rows, $model->loadPageWithCount($total, 1, 5));
        $this->assertEquals(count($rows), $total);
        $this->assertEquals([], $model->loadPageWithCount($total, 2, 5));
        $this->assertEquals(count($rows), $total);
    }
}