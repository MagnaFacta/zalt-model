<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Ra\PhpArrayModel;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class DateTimeTypesTest extends \PHPUnit\Framework\TestCase
{
    use MetaModelTestTrait;

    public function getModelLoaded($rows, array $settings = []): PhpArrayModel
    {
        $loader = $this->getModelLoader();

        if ($rows instanceof \ArrayObject) {
            $data = $rows;
        } else {
            $data = new \ArrayObject($rows);
        }
        $model = $loader->createModel(PhpArrayModel::class, 'test', $data);

        $metaModel = $model->getMetaModel();
        foreach($settings as $name => $setting) {
            $metaModel->set($name, $setting);
        }

        $metaModel->set('date', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_DATE]);
        $metaModel->set('datetime', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_DATETIME]);
        $metaModel->set('time', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_TIME]);

        return $model;
    }

    public function getRows(): array
    {
        return [
            0 => ['key' => 1, 'date' => '2022-02-02', 'datetime' => '2022-02-02 14:02:00+0200', 'time' => '14:02:00'],
            1 => ['key' => 2, 'date' => new \DateTimeImmutable('yesterday'), 'datetime' => new \DateTime('tomorrow'), 'time' => 'now'],
            2 => ['key' => 3, 'date' => null, 'datetime' => '', 'time' => 'CURRENT_TIME()'],
            3 => ['key' => 4, 'date' => 'current_date', 'datetime' => 'current_timestamp()', 'time' => 'CURRENT_TIME'],
        ];
    }

    public static function provideTypeFields(): array
    {
        return [
            'date' => ['date', MetaModelInterface::TYPE_DATE, ['date' => ['description' => 'Hallo']]],
            'datetime' => ['datetime', MetaModelInterface::TYPE_DATETIME, []],
            'time' => ['time', MetaModelInterface::TYPE_TIME, []],
            ];
    }

    public function testCreateLoader(): void
    {
        $loader = $this->getModelLoader();

        $this->assertNull($loader->getDefaultTypeInterface(-1));

        $this->assertInstanceOf(DateType::class, $loader->getDefaultTypeInterface(MetaModelInterface::TYPE_DATE));
        $this->assertInstanceOf(DateTimeType::class, $loader->getDefaultTypeInterface(MetaModelInterface::TYPE_DATETIME));
        $this->assertInstanceOf(TimeType::class, $loader->getDefaultTypeInterface(MetaModelInterface::TYPE_TIME));
    }

    /**
     * @dataProvider provideTypeFields
     *
     * @param string $field
     * @param int $type
     * @return void
     */
    public function testInitiation(string $field, int $typeId): void
    {
        $loader = $this->getModelLoader();
        $model = $this->getModelLoaded($this->getRows());

        $metaModel = $model->getMetaModel();

        $typeObject = $loader->getDefaultTypeInterface($typeId);
        $this->assertInstanceOf(AbstractDateType::class, $typeObject);

        if ($typeObject instanceof AbstractDateType) {
            $this->assertEquals($typeObject->dateFormat, $metaModel->get($field, 'dateFormat'));
            $this->assertEquals($typeObject->description, $metaModel->get($field, 'description'));
            $this->assertEquals($typeObject->size, $metaModel->get($field, 'size'));
            $this->assertEquals($typeObject->storageFormat, $metaModel->get($field, 'storageFormat'));
        }
    }

    public function testLoadConversion()
    {
        $model = $this->getModelLoaded($this->getRows());

        $data = $model->load();

        foreach ($data as $row) {
            foreach (['date', 'datetime', 'time'] as $field) {
                if (! $row[$field]) {
                    $this->assertNull($row[$field]);
                } else {
                    $this->assertInstanceOf(\DateTimeImmutable::class, $row[$field]);
                }
            }
        }
    }

    /**
     * @dataProvider provideTypeFields
     *
     * @param string $field
     * @param int $type
     * $param array $settings
     * @return void
     */
    public function testPresetNotOverwritten(string $field, int $typeId, array $settings): void
    {
        $loader = $this->getModelLoader();
        $model  = $this->getModelLoaded($this->getRows(), $settings);

        $metaModel = $model->getMetaModel();

        $typeObject = $loader->getDefaultTypeInterface($typeId);
        $this->assertInstanceOf(AbstractDateType::class, $typeObject);

        if ($typeObject instanceof AbstractDateType) {
            foreach ($settings as $field => $setting) {
                foreach ($setting as $key => $value) {
                    $this->assertEquals($value, $metaModel->get($field, $key));
                }
            }
        }
    }

    public function testSave()
    {
        $model = $this->getModelLoaded($this->getRows());

        $dateInput  = '2022-02-02 14:04:04';
        $dateFormat = $model->getMetaModel()->get('datetime', 'storageFormat');
        $dateObject = \DateTime::createFromFormat($dateFormat, $dateInput);
        $input = ['key' => 5, 'date' => \substr($dateInput, 0, 10), 'datetime' => $dateObject, 'time' => 'CURRENT_TIME()'];

        $output = $model->save($input);

        $this->assertInstanceOf(\DateTimeImmutable::class, $output['date']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $output['datetime']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $output['time']);

        if ($output['date'] instanceof \DateTimeInterface) {
            $this->assertEquals(\substr($dateInput, 0, 10), $output['date']->format($model->getMetaModel()->get('date', 'storageFormat')));
        }
        if ($output['datetime'] instanceof \DateTimeInterface) {
            $this->assertEquals($dateInput, $output['datetime']->format($dateFormat));
        }

        $output = $model->loadFirst(['key' => 5]);
        if ($output['date'] instanceof \DateTimeInterface) {
            $this->assertEquals(\substr($dateInput, 0, 10), $output['date']->format($model->getMetaModel()->get('date', 'storageFormat')));
        }
        if ($output['datetime'] instanceof \DateTimeInterface) {
            $this->assertEquals($dateInput, $output['datetime']->format($dateFormat));
        }
    }
}