<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\Exception\ModelTranslatorException;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Ra\PhpArrayModel;

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
class MappedTest extends \PHPUnit\Framework\TestCase
{
    use MetaModelTestTrait;

    public function getModelLoaded(): PhpArrayModel
    {
        $loader = $this->getModelLoader();

        $data  = new \ArrayObject();
        return $loader->createModel(PhpArrayModel::class, 'test', $data);
    }

    public function testCreation()
    {
        $modelLoader = $this->getModelLoader();

        $translator = $modelLoader->createTranslator(MappedTranslator::class);

        $this->assertInstanceOf(ModelTranslatorInterface::class, $translator);
        $this->assertInstanceOf(MappedTranslator::class, $translator);

        $this->expectException(ModelTranslatorException::class);
        $translator->getFieldsTranslations();
    }

    public function testMapped()
    {
        $model       = $this->getModelLoaded();
        $metaModel   = $model->getMetaModel();
        $modelLoader = $metaModel->getMetaModelLoader();

        $metaModel->setKeys(['id']);
        $metaModel->set('id', [
            'label' => 'id',
            'type' => MetaModelInterface::TYPE_NUMERIC,
        ]);
        $metaModel->set('a', [
            'label' => 'a',
            'type' => MetaModelInterface::TYPE_STRING,
            'multiOptions' => ['A' => 'AA', 'B' => 'BB', 'C' => 'CC'],
        ]);
        $metaModel->set('d', [
            'label' => 'd',
            'type' => MetaModelInterface::TYPE_DATE,
        ]);

        $translator = $modelLoader->createTranslator(MappedTranslator::class);
        $translator->setTargetModel($model);
        $translator->setMap([0 => 'id', 1 => 'a', 2 => 'd']);

        $this->assertInstanceOf(DataWriterInterface::class, $translator->getTargetModel());
        $this->assertIsArray($translator->getMap());

        $input = [
            [1, 'AA', '2022-06-05'],
            [2, 'BB', '2022-05-06'],
            [3, 'C', new \DateTime()],
        ];

        $output = $translator->translateImport($input);
        // print_r($output);

        $this->assertEquals('A', $output[0]['a']);
        $this->assertEquals('B', $output[1]['a']);
        $this->assertEquals('C', $output[2]['a']);
        $this->assertInstanceOf(\DateTimeInterface::class, $output[0]['d']);
        $this->assertInstanceOf(\DateTimeInterface::class, $output[2]['d']);

        $this->assertCount(3, $output);
        $this->assertCount(0, $model->load());

        $translator->saveAll($output);

        $this->assertCount(3, $model->load());
    }
    public function testNoOverlap()
    {
        $model = $this->getModelLoaded();
        $metaModel = $model->getMetaModel();
        $modelLoader = $metaModel->getMetaModelLoader();

        $metaModel->setKeys(['id']);
        $metaModel->set('id', [
            'label' => 'id',
            'type' => MetaModelInterface::TYPE_NUMERIC,
        ]);
        $metaModel->set('a', [
            'label' => 'a',
            'type' => MetaModelInterface::TYPE_STRING,
            'multiOptions' => ['A' => 'AA', 'B' => 'BB', 'C' => 'CC'],
        ]);
        $metaModel->set('d', [
            'label' => 'd',
            'type' => MetaModelInterface::TYPE_DATE,
        ]);

        $translator = $modelLoader->createTranslator(MappedTranslator::class);
        $translator->setTargetModel($model);
        $translator->setMap(['x0' => 'id', 'x1' => 'a', 'x2' => 'd']);

        $this->assertEquals(['x0' => 'id'], $translator->getRequiredFields());

        $this->assertInstanceOf(DataWriterInterface::class, $translator->getTargetModel());
        $this->assertIsArray($translator->getMap());

        $input = [
            [1, 'AA', '2022-06-05'],
            [2, 'BB', '2022-05-06'],
            ['x0' => 4, '02' => null, 'x2' => 'NULL'],
            [3, 'C', new \DateTime()],
        ];

        $output = $translator->translateImport($input);
        $this->assertTrue($translator->hasErrors());

        $expected = [
            "Row 0: No field overlap between source and target",
            "Row 1: No field overlap between source and target",
            "Row 3: No field overlap between source and target",
        ];
        $this->assertEquals($expected, $translator->getErrors());
    }
}