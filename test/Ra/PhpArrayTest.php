<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Ra;

use PHPUnit\Framework\TestCase;
use Zalt\Late\RepeatableInterface;
use Zalt\Model\Bridge\DisplayBridge;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\MetaModelTestTrait;

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @since      Class available since version 1.0
 */
class PhpArrayTest extends TestCase
{
    use MetaModelTestTrait;

    public function getModelLoaded(array $rows): PhpArrayModel
    {
        $loader = $this->getModelLoader();

        $data  = new \ArrayObject($rows);

        // @phpstan-ignore return.type
        return $loader->createModel(PhpArrayModel::class, 'test', $data);
    }

    public function getRows(): array
    {
        return [
            0 => ['a' => 'A1', 'b' => 'B1', 'c' => 20],
            1 => ['a' => 'A2', 'b' => 'B2', 'c' => 40],
            2 => ['a' => 'A3', 'b' => 'C3', 'c' => 10],
            3 => ['a' => 'A4', 'b' => 'D4', 'c' => 30],
            ];
    }

    public function testCreation(): void
    {
        $loader = $this->getModelLoader();

        $rows  = [];
        $data  = new \ArrayObject($rows);
        $model = $loader->createModel(PhpArrayModel::class, 'test', $data);

        $this->assertInstanceOf(PhpArrayModel::class, $model);

        $bridge1 = $model->getBridgeFor(DisplayBridge::class);
        $this->assertInstanceOf(DisplayBridge::class, $bridge1);

        $bridge2 = $model->getBridgeFor(DisplayBridge::class);
        $this->assertInstanceOf(DisplayBridge::class, $bridge2);
        $this->assertNotSame($bridge1, $bridge2);
    }

    public function testDelete()
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);
        $model->getMetaModel()->setKeys(['a']);

        $model->getMetaModel()->setKeys(['a']);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $this->assertEquals(count($rows), $model->loadCount());
        $this->assertEquals(1, $model->delete(['a' => 'A2']));
        $this->assertEquals(count($rows) - 1, $model->loadCount());
    }

    public function testLoad(): void
    {
        $loader = $this->getModelLoader();

        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $this->assertEquals($rows, $model->load());
    }

    public function testLoader(): void
    {
        $loader = $this->getModelLoader();

        $this->assertInstanceOf(MetaModelLoader::class, $loader);
    }

    public function testLoadFilter(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $this->assertEquals([$rows[1]], $model->load(['a' => 'A2']));
    }

    public function testLoadFilterBetween(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[0],
            $rows[3],
        ];
        $this->assertEquals($output, $model->load(['c' => [MetaModelInterface::FILTER_BETWEEN_MIN => 20, MetaModelInterface::FILTER_BETWEEN_MAX => 30]]));
    }

    public function testLoadFilterCallable(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[1],
            $rows[3],
        ];
        $this->assertEquals($output, $model->load(['c' => function ($value) { return $value > 20; }], 'a'));
    }

    public function testLoadFilterCallArray(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[1],
            $rows[3],
        ];
        $this->assertEquals($output, $model->load([function ($row) { return $row['c'] > 20; }], 'a'));
    }

    public function testLoadFilterIn(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[2],
            $rows[0],
        ];
        $this->assertEquals($output, $model->load(['a' => ['A1', 'A3']], 'c'));
    }

    public function testLoadFilterLike(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $this->assertEquals([$rows[2]], $model->load(['b' => [MetaModelInterface::FILTER_CONTAINS => 'C']]));
    }

    public function testLoadFilterLikeNot(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $this->assertEquals([$rows[2], $rows[3]], $model->load(['b' => [MetaModelInterface::FILTER_CONTAINS_NOT => 'B']]));
    }

    public function testLoadFilterNot(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $this->assertEquals([$rows[0], $rows[2], $rows[3]], $model->load([MetaModelInterface::FILTER_NOT => ['a' => 'A2']]));
    }

    public function testLoadNew()
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $metaModel = $model->getMetaModel();
        $metaModel->set('b', 'default', 'XX');
        $metaModel->set('c', ['default' => 0]);

        $newRow = ['b' => 'XX', 'c' => 0];

        $this->assertEquals($newRow, $model->loadNew());
    }

    public function testLoadPageWithCount1(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);
        $total = 0;

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[0],
            $rows[2],
        ];
        $result = $model->loadPageWithCount($total, 1, 2, ['c' => [MetaModelInterface::FILTER_BETWEEN_MIN => 10, MetaModelInterface::FILTER_BETWEEN_MAX => 30]]);
        $this->assertEquals($output, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(3, $total);
    }

    public function testLoadPageWithCount2(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);
        $total = 0;

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[2],
            $rows[3],
        ];
        $result = $model->loadPageWithCount($total, 2, 2);
        $this->assertEquals($output, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(4, $total);
    }

    public function testLoadPage1(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[0],
            $rows[2],
        ];
        $result = $model->loadPage(1, 2, ['c' => [MetaModelInterface::FILTER_BETWEEN_MIN => 10, MetaModelInterface::FILTER_BETWEEN_MAX => 30]]);
        $this->assertEquals($output, $result);
        $this->assertCount(2, $result);
    }

    public function testLoadPage2(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[2],
            $rows[3],
        ];
        $result = $model->loadPage(2, 2);
        $this->assertEquals($output, $result);
        $this->assertCount(2, $result);
    }

    public function testLoadRepeater(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(RepeatableInterface::class, $model->loadRepeatable(['c' => 100]));

        $repeater = $model->loadRepeatable();
        $this->assertInstanceOf(RepeatableInterface::class, $repeater);
    }

    public function testLoadSort1(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $this->assertEquals(array_reverse($rows), $model->load(null, ['a' => SORT_DESC]));
    }

    public function testLoadSort2(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[2],
            $rows[0],
            $rows[3],
            $rows[1],
        ];
        $this->assertEquals($output, $model->load(null, ['c' => SORT_ASC]));
    }

    public function testLoadWithSettings(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $this->assertInstanceOf(PhpArrayModel::class, $model);
        $output = [
            $rows[2],
            $rows[0],
        ];
        $this->assertFalse($model->hasFilter());
        $this->assertFalse($model->hasSort());

        $model->setFilter(['a' => ['A1', 'A3']]);
        $model->setSort(['c']);

        $this->assertEquals($output, $model->load());

        $this->assertEquals($rows[2], $model->loadFirst());
        $this->assertEquals([], $model->loadFirst(['a' => 'XX']));

        $this->assertTrue($model->hasFilter());
        $this->assertTrue($model->hasSort());
    }

    public function testSaveNew(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $input = ['a' => 'A5', 'b' => 'D5', 'c' => 5];

        $output = $model->save($input);
        $this->assertEquals($output, $input);
        $this->assertCount(5, $model->load());
        $this->assertEquals(1, $model->getChanged());
    }

    public function testSaveKeyChange(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $input = ['a' => 'A6', 'b' => 'D5', 'c' => 5];
        $model->getMetaModel()->setKeys(['a']);

        $output = $model->save($input, ['a' => 'A1']);
        $this->assertEquals($output, $input);
        $this->assertCount(4, $model->load());
        $this->assertEquals(1, $model->getChanged());
    }

    public function testSaveKnown(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $input = ['a' => 'A2', 'b' => 'D5', 'c' => 5];
        $model->getMetaModel()->setKeys(['a']);

        $output = $model->save($input);
        $this->assertEquals($output, $input);
        $this->assertCount(4, $model->load());
        $this->assertEquals(1, $model->getChanged());
    }

    public function testSaveKnownPartial(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $input = ['a' => 'A1', 'b' => 'D5']; // Leave out field c
        $model->getMetaModel()->setKeys(['a']);

        $output = $model->save($input);
        $this->assertEquals($output, $input + ['c' => 20]);
        $this->assertCount(4, $model->load());
        $this->assertEquals(1, $model->getChanged());
    }

    public function testSaveNoChange(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $input = $rows[1];
        $model->getMetaModel()->setKeys(['a']);

        $output = $model->save($input);
        $this->assertEquals($output, $input);
        $this->assertCount(4, $model->load());
        $this->assertEquals(0, $model->getChanged());
    }

    public function testSaveNoKey(): void
    {
        $rows  = $this->getRows();
        $model = $this->getModelLoaded($rows);

        $input = ['a' => 'A1', 'b' => 'D5', 'c' => 5];

        $output = $model->save($input);
        $this->assertEquals($output, $input);
        $this->assertCount(5, $model->load());
        $this->assertEquals(1, $model->getChanged());
    }
}