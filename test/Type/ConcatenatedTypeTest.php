<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Html\HtmlElement;
use Zalt\Html\HtmlInterface;
use Zalt\Html\Sequence;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Ra\PhpArrayModel;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class ConcatenatedTypeTest extends \PHPUnit\Framework\TestCase
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
        $metaModel->set('concat');

        return $model;
    }

    public static function providerTimes(): array
    {
        return [
            'typeDefault' => [' 1 2 ', [1, 2], '1 2', ' 1 2 ', ' ', ' ', true],
            'lessPads1' => ['1 2', [1, 2], '1 2', ' 1 2 ', ' ', ' ', true],
            'lessPads2' => [' 1 2', [1, 2], '1 2', ' 1 2 ', ' ', ' ', true],
            'lessPads3' => ['1 2 ', [1, 2], '1;2', ' 1 2 ', ' ', ';', true],
            'noPads1' => ['1:2', [1, 2], '1; 2', '1:2', ':', '; ', false],
            'noPads2' => [':1:2', ['', 1, 2], '; 1; 2', ':1:2', ':', '; ', false],
            'noPads3' => ['1:2:', [1, 2, ''], '1; 2; ', '1:2:', ':', '; ', false],
            'noPads4' => [':1:2:', ['', 1, 2, ''], '; 1; 2; ', ':1:2:', ':', '; ', false],
            'dashes' => ['-1-2-', [1, 2], '1  2', '-1-2-', '-', '  ', true],
            'empty' => ['||', [], '', '||', '|', "\n", true],
            'pipes' => ['|1|2|', [1, 2], '1<br />2', '|1|2|', '|', new HtmlElement('br'), true],
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
    public function testToStrings(string $input, array $loaded, string $display, string $stored, string $seperatorChar, mixed $displaySeperator, bool $valuePad): void
    {
        $row       = ['id' => 1, 'concat' => $input];
        $type      = new ConcatenatedType($seperatorChar, $displaySeperator, $valuePad);
        $this->assertCount(3, $type->getSettings());

        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('concat', [
            MetaModelInterface::TYPE_ID => $type,
        ]);

        $data = $model->loadFirst(['id' => 1]);

        // Check onLoad
        $this->assertIsArray($data['concat']);
        $this->assertEquals($loaded, $data['concat']);

        // Check display value
        $bridge = $model->getBridgeFor('display');
        $output = $bridge->format('concat', $data['concat']);
        if ($output instanceof HtmlInterface) {
            $output = $output->render();
        }
        $this->assertEquals($display, $output);

//        // Check storage of original data (no change)
        $save = $metaModel->processRowBeforeSave($data);
        $this->assertEquals($stored, $save['concat']);
//        $this->assertEquals(0, $model->getChanged());
    }

    public function testAlreadyArray(): void
    {
        $row       = ['id' => 1, 'concat' => [1, 2]];
        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('concat', [
            'multiOptions' => [1 => 'Yes', 2 => 'No', 3 => 'Maybe'],
            MetaModelInterface::TYPE_ID => new ConcatenatedType('|', ' ', false),
        ]);

        $bridge = $model->getBridgeFor('display');
        $this->assertEquals('Yes No', $bridge->format('concat', $row['concat']));
    }
}