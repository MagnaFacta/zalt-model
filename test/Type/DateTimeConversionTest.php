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
class DateTimeConversionTest extends \PHPUnit\Framework\TestCase
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
        $metaModel->set('datetime', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_DATETIME]);

        return $model;
    }


    public static function provideDateTimes(): array
    {
        return [
            'typeDefault' => ['2017-06-05 04:03:02', '05-06-2017 04:03', '2017-06-05 04:03:00', 'd-m-Y H:i', 'Y-m-d H:i:s'],
            'shortYear'   => ['2017-06-05 04:03:02', '05-06-17 04:03', '2017-06-05 04:03:00', 'd-m-y H:i', 'Y-m-d H:i:s'],
            'monthName'   => ['2017-06-05 04:03:02', '5 Jun 2017 4:03', '2017-06-05 04:03:00', 'j M Y G:i', 'Y-m-d H:i:s'],
            'dayName'     => ['2017-06-05 04:03:02', 'Mon 5 Jun 2017 4:03', '2017-06-05 04:03:00', 'D j M Y G:i', 'Y-m-d H:i:s'],
            'shortAll'    => ['2017-06-05 04:03:02', '5-6-17 4:03', '2017-06-05 04:03:00', 'j-n-y G:i', 'Y-m-d H:i:s'],
            'usStorage'   => ['06/05/2017 04:03:02', '5-6-17 4:03', '06/05/2017 04:03:00', 'j-n-y G:i', 'm/d/Y H:i:s'],
            'usDisplay1'  => ['2017-06-05 04:03:02', '06/05/17 4:03 am', '2017-06-05 04:03:00', 'm/d/y g:i a', 'Y-m-d H:i:s'],
            'usDisplay2'  => ['2017-06-05 14:03:02', '06/05/17 02:03 pm', '2017-06-05 14:03:00', 'm/d/y h:i a', 'Y-m-d H:i:s'],
            'usSpaced'    => ['2017 06 05 04:03:02', '6 5 17 4 03', '2017 06 05 04:03:00', 'n j y G i', 'Y m d H:i:s'],
        ];
    }

    /**
     * @dataProvider provideDateTimes
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
        $row       = ['id' => 1, 'datetime' => $input];
        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('datetime', [
            'dateFormat'    => $displayFormat,
            'storageFormat' => $storageFormat,
        ]);

        $data = $model->loadFirst(['id' => 1]);
        $time = $data['datetime'];

        // Check onLoad
        $this->assertInstanceOf(\DateTimeImmutable::class, $time);
        $this->assertEquals($display, $time->format($displayFormat));

        // Check display value
        $bridge = $model->getBridgeFor('display');
        $this->assertEquals($display, $bridge->format('datetime', $time));

        // Check storage of original data (no change)
        $save = $metaModel->processRowBeforeSave($data);
        $this->assertEquals($input, $save['datetime']);
        $this->assertEquals(0, $model->getChanged());

        // Mimioc post
        $data['datetime'] = $display;
        $post = $model->loadPostData($data, false);
        $this->assertInstanceOf(\DateTimeImmutable::class, $time);
        $this->assertEquals($display, $time->format($displayFormat));

        // Save post
        $save = $metaModel->processRowBeforeSave($post);
        $this->assertEquals($storage, $save['datetime']);
        $this->assertEquals(0, $model->getChanged());
    }
}