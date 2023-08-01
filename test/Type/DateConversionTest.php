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
class DateConversionTest extends \PHPUnit\Framework\TestCase
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
        $metaModel->set('date', [MetaModelInterface::TYPE_ID => MetaModelInterface::TYPE_DATE]);

        return $model;
    }


    public static function provideDates(): array
    {
        return [
            'typeDefault' => ['2017-06-05', '05-06-2017', '2017-06-05', 'd-m-Y', 'Y-m-d'],
            'shortYear'   => ['2017-06-05', '05-06-17', '2017-06-05', 'd-m-y', 'Y-m-d'],
            'monthName'   => ['2017-06-05', '5 Jun 2017', '2017-06-05', 'j M Y', 'Y-m-d'],
            'dayName'     => ['2017-06-05', 'Mon 5 Jun 2017', '2017-06-05', 'D j M Y', 'Y-m-d'],
            'shortAll'    => ['2017-06-05', '5-6-17', '2017-06-05', 'j-n-y', 'Y-m-d'],
            'usStorage'   => ['06/05/2017', '5-6-17', '06/05/2017', 'j-n-y', 'm/d/Y'],
            'usDisplay'   => ['2017-06-05', '06/05/17', '2017-06-05', 'm/d/y', 'Y-m-d'],
            'usSpaced'    => ['2017 06 05', '6 5 17', '2017 06 05', 'n j y', 'Y m d'],
        ];
    }

    /**
     * @dataProvider provideDates
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
        $row       = ['id' => 1, 'date' => $input];
        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('date', [
            'dateFormat'    => $displayFormat,
            'storageFormat' => $storageFormat,
        ]);

        $data = $model->loadFirst(['id' => 1]);
        $time = $data['date'];

        // Check onLoad
        $this->assertInstanceOf(\DateTimeImmutable::class, $time);
        $this->assertEquals($display, $time->format($displayFormat));

        // Check display value
        $bridge = $model->getBridgeFor('display');
        $this->assertEquals($display, $bridge->format('date', $time));

        // Check storage of original data (no change)
        $save = $metaModel->processRowBeforeSave($data);
        $this->assertEquals($input, $save['date']);
        $this->assertEquals(0, $model->getChanged());

        // Mimioc post
        $data['date'] = $display;
        $post = $model->loadPostData($data, false);
        $this->assertInstanceOf(\DateTimeImmutable::class, $time);
        $this->assertEquals($display, $time->format($displayFormat));

        // Save post
        $save = $metaModel->processRowBeforeSave($post);
        $this->assertEquals($storage, $save['date']);
        $this->assertEquals(0, $model->getChanged());
    }
}