<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

use ArrayIterator;
use ArrayObject;
use Laminas\Validator\Date;
use Laminas\Validator\Digits;
use Laminas\Validator\InArray;
use Laminas\Validator\NotEmpty;
use Zalt\Late\Repeatable;
use Zalt\Late\RepeatableByKeyValue;
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
class StraightTest extends \PHPUnit\Framework\TestCase
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

        $translator = $modelLoader->createTranslator(StraightTranslator::class);

        $this->assertInstanceOf(ModelTranslatorInterface::class, $translator);
        $this->assertInstanceOf(StraightTranslator::class, $translator);

        $this->expectException(ModelTranslatorException::class);
        $translator->startImport();
    }

    public function testException()
    {
        $model       = $this->getModelLoaded();
        $metaModel   = $model->getMetaModel();
        $modelLoader = $metaModel->getMetaModelLoader();

        $translator = $modelLoader->createTranslator(StraightTranslator::class);
        $translator->setTargetModel($model);

        $describe = 'describe';
        $translator->setDescription($describe);
        $this->assertEquals($describe, $translator->getDescription());

        $this->assertInstanceOf(DataWriterInterface::class, $translator->getTargetModel());

        $input = ['id' => 1, 'a' => 'A', 'd' => '2022-06-05', 'dt' => '2022-06-05 20:22:24', 't' => '20:22:24'];
        $this->expectException(ModelTranslatorException::class);
        $translator->translateRowValues($input, 0);
    }

    public function testNotAnArray()
    {
        $model       = $this->getModelLoaded();
        $metaModel   = $model->getMetaModel();
        $modelLoader = $metaModel->getMetaModelLoader();

        $translator = $modelLoader->createTranslator(StraightTranslator::class);
        $translator->setTargetModel($model);
        $this->assertInstanceOf(DataWriterInterface::class, $translator->getTargetModel());

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

        $input = ['id' => 1, 'a' => 'A', 'd' => '2022-06-05'];
        $translator->startImport();

        $this->assertIsArray($translator->translateRowValues(new ArrayIterator($input), 0));
        $this->assertIsArray($translator->translateRowValues(new ArrayObject($input), 0));
        $this->assertIsArray($translator->translateRowValues(new Repeatable([$input]), 0));

        $this->assertFalse($translator->translateRowValues("just some text", 0));
        $expected= ["Row 0: Input is not an array"];
        $this->assertEquals($expected, $translator->getErrors());
    }

    public function testNoValidators()
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
        $metaModel->set('dt', [
            'label' => 'dt',
            'type' => MetaModelInterface::TYPE_DATETIME,
        ]);
        $metaModel->set('t', [
            'label' => 't',
            'type' => MetaModelInterface::TYPE_TIME,
        ]);

        $translator = $modelLoader->createTranslator(StraightTranslator::class);
        $translator->setTargetModel($model);

        $this->assertInstanceOf(DataWriterInterface::class, $translator->getTargetModel());

        $input = [
            ['id' => 1, 'a' => 'AA', 'd' => '2022-06-05', 'dt' => '2022-06-05 20:22:24', 't' => '20:22:24'],
            ['id' => 2, 'a' => 'BB', 'd' => '2022-05-06', 'dt' => '2022-05-06 02:22:24', 't' => '02:22:24'],
            ['id' => 3, 'a' => 'C', 'd' => new \DateTime(), 'dt' => 'NULL', 't' => null],
            ];

        $output = $translator->translateImport($input);
        // print_r($output);

        $this->assertEquals('A', $output[0]['a']);
        $this->assertEquals('B', $output[1]['a']);
        $this->assertEquals('C', $output[2]['a']);
        $this->assertInstanceOf(\DateTimeInterface::class, $output[0]['d']);
        $this->assertInstanceOf(\DateTimeInterface::class, $output[2]['d']);
        $this->assertNull($output[2]['dt']);
        $this->assertNull($output[2]['t']);

        $this->assertCount(3, $output);
        $this->assertCount(0, $model->load());

        $translator->saveAll($output);

        $this->assertCount(3, $model->load());
    }

    public function testValidators()
    {
        $model       = $this->getModelLoaded();
        $metaModel   = $model->getMetaModel();
        $modelLoader = $metaModel->getMetaModelLoader();

        $metaModel->setKeys(['id']);
        $metaModel->set('id', [
            'label' => 'id',
            'type' => MetaModelInterface::TYPE_NUMERIC,
            'validators[int]' => Digits::class,
        ]);
        $options = ['A' => 'AA', 'B' => 'BB', 'C' => 'CC'];
        $metaModel->set('a', [
            'label' => 'a',
            'type' => MetaModelInterface::TYPE_STRING,
            'multiOptions' => $options,
            'validators[notEmpty]' => NotEmpty::class,
            'validators[oneOf]' => [InArray::class, false, ['haystack' => array_keys($options)]],
        ]);
        $metaModel->set('d', [
            'label' => 'd',
            'type' => MetaModelInterface::TYPE_DATE,
            'validators[date]' => Date::class,
        ]);

        $translator = $modelLoader->createTranslator(StraightTranslator::class);
        $translator->setTargetModel($model);

        $this->assertInstanceOf(DataWriterInterface::class, $translator->getTargetModel());

        $input = [
            ['id' => 1, 'a' => 'AA', 'd' => '2022-06-05'],
            ['id' => 2, 'a' => 'BB', 'd' => '2022-05-06'],
            ['id' => 3, 'a' => 'C', 'd' => new \DateTime()],
        ];

        $output = $translator->translateImport($input);
        // print_r($translator->getErrors());
        $this->assertFalse($translator->hasErrors());
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

    public function testValidatorsWithErrors()
    {
        $model       = $this->getModelLoaded();
        $metaModel   = $model->getMetaModel();
        $modelLoader = $metaModel->getMetaModelLoader();

        $metaModel->setKeys(['id']);
        $metaModel->set('id', [
            'label' => 'id',
            'type' => MetaModelInterface::TYPE_NUMERIC,
            'validators[int]' => Digits::class,
        ]);
        $options = ['A' => 'AA', 'B' => 'BB', 'C' => 'CC'];
        $metaModel->set('a', [
            'label' => 'a',
            'type' => MetaModelInterface::TYPE_STRING,
            'multiOptions' => $options,
            'validators[notEmpty]' => NotEmpty::class,
            'validators[oneOf]' => [InArray::class, false, ['haystack' => array_keys($options)]],
        ]);
        $metaModel->set('d', [
            'label' => 'd',
            'type' => MetaModelInterface::TYPE_DATE,
            'validators[date]' => Date::class,
        ]);

        $translator = $modelLoader->createTranslator(StraightTranslator::class);
        $translator->setTargetModel($model);

        $this->assertInstanceOf(DataWriterInterface::class, $translator->getTargetModel());

        $input = [
            ['id' => 'A1', 'a' => 'D', 'd' => '2022-06-05'],
            ['id' => 2, 'a' => 'NULL', 'd' => 'not a date'],
            ['id' => 3, 'a' => null, 'd' => new \DateTime()],
        ];

        $output = $translator->translateImport($input);
        $this->assertTrue($translator->hasErrors());

        $expectedErrors = [
            "Row 0 field id: The input must contain only digits",
            "Row 0 field a: The input was not found in the haystack",
            "Row 1 field a: Value is required and can't be empty",
            "Row 1 field a: The input was not found in the haystack",
            "Row 1 field d: The input does not appear to be a valid date",
            "Row 2 field a: Value is required and can't be empty",
            "Row 2 field a: The input was not found in the haystack",
            ];
        // print_r($translator->getErrors());
        $this->assertEquals($expectedErrors, $translator->getErrors());
    }
}