<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

/**
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class MetaModelTest extends \PHPUnit\Framework\TestCase
{
    use MetaModelTestTrait;

    public function getEmptyModel(): MetaModel
    {
        return new MetaModel('test1', $this->getModelLoader());
    }

    public function testGetSetDel()
    {
        $mm = $this->getEmptyModel();

        $this->assertInstanceOf(MetaModel::class, $mm);

        $mm->set('test1', [
            'field1' => 1,
            'field2' => 'two',
            'field3[]' => 'three',
            'field4[1]' => 'one',
            'field4[2]' => 'two',
            'field5' => null,
            'field6[]' => null,
            ]);
        $mm->set('test2',
            'field1', 1,
            'field2', 'two',
            'field3[]', 'three',
            'field4[1]', 'one',
            'field4[2]', 'two',
            'field5', null,
            'field6[]', null,
        );

        $this->assertTrue($mm->hasAnyOf(['test1', 'test2']));
        $this->assertFalse($mm->hasAnyOf(['test5', 'test6']));

        $fields = ['test1', 'test2'];
        $this->assertEquals($fields, $mm->getItemNames());
        foreach ($fields as $field) {
            $this->assertCount(5, $mm->get($field));
            $this->assertEquals(1, $mm->get($field, 'field1'));

            $this->assertNull($mm->get($field, 'field0'));

            $this->assertEquals('two', $mm->get($field, 'field2'));
            $mm->del($field, 'field2');
            $this->assertFalse($mm->has($field, 'field2'));
            $this->assertNull($mm->get($field, 'field2'));

            $this->assertCount(1, $mm->get($field, 'field3'));
            $mm->remove($field, 'field3');
            $this->assertFalse($mm->has($field, 'field3'));
            $this->assertNull($mm->get($field, 'field3'));

            $this->assertCount(2, $mm->get($field, 'field4'));

            $this->assertFalse($mm->has($field, 'field5'));
            $this->assertNull($mm->get($field, 'field5'));

            $this->assertTrue($mm->has($field, 'field6'));
            $this->assertCount(1, $mm->get($field, 'field6'));
            $this->assertNull($mm->get($field, 'field6[0]'));
            $this->assertNull($mm->get($field, 'field6[1]'));
            $this->assertNull($mm->get($field, 'field6')[0]);
            $this->assertArrayNotHasKey(1, $mm->get($field, 'field6'));
            $mm->remove($field, 'field6');
            $this->assertFalse($mm->has($field, 'field6'));
            $this->assertNull($mm->get($field, 'field6'));
        }
        $this->assertEquals($fields, $mm->getItemsOrdered());

        $mm->set('test3', ['field1' => 3]);

        $mm->setCol('field5', 5, 'field6', 6);
        foreach ($fields as $i => $field) {
            $this->assertEquals(5, $mm->get($field, 'field5'));
            $this->assertEquals(6, $mm->get($field, 'field6'));
            $this->assertEquals(($i + 1) * $mm->orderIncrement, $mm->getOrder($field));
        }

        $mm->setCol(['test2'], 'field7', 7, 'field8', 8);
        $this->assertNull($mm->get('test1', 'field7'));
        $this->assertNull($mm->get('test1', 'field8'));
        $this->assertEquals(7, $mm->get('test2', 'field7'));
        $this->assertEquals(8, $mm->get('test2', 'field8'));

        $mm->setCol(['field9' => 9, 'field10' => 10]);
        foreach ($fields as $field) {
            $this->assertEquals(9, $mm->get($field, 'field9'));
            $this->assertEquals(10, $mm->get($field, 'field10'));
        }
        $this->assertEquals(['test2'], $mm->getColNames('field7'));

        $mm->del('test2');
        $this->assertCount(2, $mm->getItemNames());
        $this->assertEquals(['test1', 'test3'], $mm->getItemsOrdered());

        $mm->remove('test3');
        $this->assertCount(1, $mm->getItemNames());
        $this->assertEquals(['test1'], $mm->getItemsOrdered());
    }

    public function testUsage()
    {
        $mm = $this->getEmptyModel();

        $fields = ['test1', 'test2', 'test3'];
        foreach ($fields as $field) {
            $mm->set($field, ['a' => 'b', 'c' => 'd']);
        }
        $this->assertEquals($fields, $mm->getItemsOrdered());

        $mm->trackUsage(false);
        $this->assertFalse($mm->hasItemsUsed());
        $mm->trackUsage(true);
        $this->assertFalse($mm->hasItemsUsed());
        $this->assertEmpty($mm->getKeys());
        // No keys set so returns all columnds
        $this->assertCount(3, $mm->getItemsUsed());
        $mm->get('test2');
        $this->assertTrue($mm->hasItemsUsed());
        $this->assertCount(1, $mm->getItemsUsed());
        $mm->get('test3');
        $this->assertTrue($mm->hasItemsUsed());
        $this->assertCount(2, $mm->getItemsUsed());
    }
}