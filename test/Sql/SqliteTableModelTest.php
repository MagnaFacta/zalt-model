<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use MUtil\Model\TableModel;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Sql\Laminas\LaminasRunner;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class SqliteTableModelTest extends \PHPUnit\Framework\TestCase
{
    use MetaModelTestTrait;
    use SqliteUseTrait;

    public function testInsert(): void
    {
        $adapter = $this->getAdapter();
        $this->createFillDb($adapter, __DIR__ . '/../data/basicDb');

        $sm = $this->getServiceManager();
        $sm->set(SqlRunnerInterface::class, new LaminasRunner($adapter));
        $modelLoader = $this->getModelLoader();

        $model = $modelLoader->createModel(SqlTableModel::class, 't1');

        $this->assertInstanceOf(SqlTableModel::class, $model);
        $this->assertInstanceOf(FullDataInterface::class, $model);

        $model->save(['id' => 1, 'c1' => 'col1-1']);
        $this->assertEquals(0, $model->getChanged());

        $model->save(['id' => 1, 'c1' => 'col1-1b']);
        $this->assertEquals(1, $model->getChanged());

        $this->assertCount(2, $model->load());

        $model->save(['c1' => 'col1-3', 'c2' => 'col2-3']);
        $this->assertEquals(2, $model->getChanged());
        $this->assertCount(3, $model->load());
    }

}