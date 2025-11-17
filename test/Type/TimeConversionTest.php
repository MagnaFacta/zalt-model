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
class TimeConversionTest extends \PHPUnit\Framework\TestCase
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

        $metaModel->set('id', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_NUMERIC, 'key' => true]);
        $metaModel->set('time', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_TIME]);

        // @phpstan-ignore return.type
        return $model;
    }

    public static function providerTimes(): array
    {
        return [
            'typeDefault' => ['15:01:17', '15:01', '15:01:00', 'H:i', 'H:i:s'],
            'fullTime'    => ['15:01:17', '15:01:17', '15:01:17', 'H:i:s', 'H:i:s'],
            'minuteFirst' => ['15:01:17', '01:15', '15:01:00', 'i:H', 'H:i:s'],
            'dash'        => ['15-01-17', '15-01', '15-01-00', 'H-i', 'H-i-s'],
            'noColons'    => ['150117', '1501', '150100', 'Hi', 'His'],
            'spaceCase1'  => ['15 01 17', '15 01', '15 01 00', 'G i', 'H i s'],
            'spaceCase2'  => ['05 01 17', '5 01', '05 01 00', 'G i', 'H i s'],
            'spaceCase3'  => ['5 01 17', '05 01', '5 01 00', 'H i', 'G i s'],
            'spaceCase4'  => ['5 01 17', '5 01', '5 01 00', 'G i', 'G i s'],
            'reverse'     => ['17:01:15', '01:15', '00:01:15', 'i:H', 's:i:H'],
        ];
    }

    /**
     * @dataProvider providerTimes
     *
     * @param string $input
     * @param string $display
     * @param string $storage
     * @param string $displayFormat
     * @param string $storageFormat
     * @return void
     * @throws \Zalt\Model\Exception\ModelException
     */
    public function testToStrings(string $input, string $display, string $storage, string $displayFormat, string $storageFormat): void
    {
        $row       = ['id' => 1, 'time' => $input];
        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('time', [
            'dateFormat'    => $displayFormat,
            'storageFormat' => $storageFormat,
        ]);

        $data = $model->loadFirst(['id' => 1]);

        // Check onLoad
        $this->assertInstanceOf(\DateTimeImmutable::class, $data['time']);
        $this->assertEquals($display, $data['time']->format($displayFormat));

        // Check display value
        $bridge = $model->getBridgeFor('display');
        $this->assertEquals($display, $bridge->format('time', $data['time']));

        // Check storage of original data (no change)
        $save = $metaModel->processRowBeforeSave($data);
        $this->assertEquals($input, $save['time']);
        $this->assertEquals(0, $model->getChanged());

        // Mimioc post
        $data['time'] = $display;
        $post = $model->loadPostData($data, false);
        $this->assertInstanceOf(\DateTimeImmutable::class, $post['time']);
        $this->assertEquals($display, $post['time']->format($displayFormat));

        // Save post
        $save = $metaModel->processRowBeforeSave($post);
        $this->assertEquals($storage, $save['time']);
        $this->assertEquals(0, $model->getChanged());
    }
}