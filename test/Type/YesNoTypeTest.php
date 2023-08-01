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
class YesNoTypeTest extends \PHPUnit\Framework\TestCase
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
        $metaModel->set('yesno');

        return $model;
    }

    public static function providerYesNo(): array
    {
        return [
            'YesNo1' => [0, 'Yes', ['Yes', 'No']],
            'YesNo2' => [1, 'No', ['Yes', 'No']],
            'NoYes1' => [0, 'No', ['No', 'Yes']],
            'NoYes2' => [1, 'Yes', ['No', 'Yes']],
            'classy' => [1, 'Yes', ['No', 'Yes'], 'class'],
        ];
    }

    /**
     * @dataProvider providerYesNo
     *
     * @param string $input
     * @param string $display
     * @param string $init
     * @return void
     * @throws \Zalt\Model\Exception\ModelException
     */
    public function testToStrings(int $input, string $display, array $init, string $className = ''): void
    {
        $row       = ['id' => 1, 'yesno' => $input];
        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('yesno', [
            MetaModelInterface::TYPE_ID => new YesNoType($init, $className),
        ]);

        $data = $model->loadFirst(['id' => 1]);

        // Check onLoad
        $this->assertEquals($input, $data['yesno']);

        // Check display value
        $bridge = $model->getBridgeFor('display');
        $this->assertEquals($display, $bridge->format('yesno', $data['yesno']));

        if ($className) {
            $this->assertTrue($metaModel->has('yesno', 'column_expression'));
        } else {
            $this->assertFalse($metaModel->has('yesno', 'column_expression'));
        }
    }

}