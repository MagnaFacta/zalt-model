<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

use PHPUnit\Framework\TestCase;
use Zalt\Late\Late;
use Zalt\Late\LateInterface;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Ra\PhpArrayModel;

/**
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
class DisplayBridgeTest extends TestCase
{
    use MetaModelTestTrait;

    public function getModelLoaded(array $row): PhpArrayModel
    {
        $loader = $this->getModelLoader();

        $data  = new \ArrayObject([$row]);

        // @phpstan-ignore return.type
        return $loader->createModel(PhpArrayModel::class, 'test', $data);
    }

    public static function alwaysOne(mixed $value): string
    {
        return 'one';
    }

    public static function provideDecimals(): array
    {
        return [
            [['field' => 2345.123], 2, 'en-US', '2,345.12'],
            [['field' => 2345.123], 2, 'nl-NL', '2.345,12'],
            [['field' => 2345.123], 2, 'fr-FR', '2' . chr(160)  . '345,12'],
            [['field' => 2345.123], 3, 'en-US', '2,345.123'],
            [['field' => 2345.126], 2, 'en-US', '2,345.13'],
            [['field' => 2345.123], 1, 'en-US', '2,345.1'],
            [['field' => 2345.173], 1, 'en-US', '2,345.2'],
            [['field' => 2345.123], 4, 'nl-NL', '2.345,1230'],
            [['field' => 2345.1270], 2, 'nl-NL', '2.345,13'],
            [['field' => null], 4, 'nl-NL', null],
            [['field' => 2345], 2, 'en-US', '2,345.00'],
            [['field' => 0], 2, 'en-US', '0.00'],
            [['field' => 0], 3, 'en-US', '0.000'],
            [['field' => 0.000], 2, 'en-US', '0.00'],
            ];
    }

    /**
     * @dataProvider provideDecimals
     * @param array $row
     * @param int $decimals
     * @param string $locale
     * @param string $output
     * @return void
     * @throws \Zalt\Model\Exception\MetaModelException
     */
    public function testDecimals(array $row, int $decimals, string $locale, ?string $output)
    {
        if (! setlocale(LC_ALL, $locale, substr($locale, 0, 2))) {
            return;
        }

        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('field', [
            'decimals' => $decimals,
        ]);

        $bridge    = new DisplayBridge($model);
        $formatter = $bridge->getFormatted('field');
        $this->assertInstanceOf(LateInterface::class, $formatter);

        $bridge->setRow($row);
        $value = Late::rise($formatter);
        $this->assertEquals($output, $value);
    }

    public static function provideNumberFormat(): array
    {
        return [
            [['field' => 2345.123], 2, 'en-US', '2,345.12'],
            [['field' => 2345.123], 2, 'nl-NL', '2.345,12'],
            [['field' => 2345.123], 2, 'fr-FR', '2' . chr(160)  . '345,12'],
            [['field' => 2345.123], 3, 'en-US', '2,345.123'],
            [['field' => 2345.126], 2, 'en-US', '2,345.13'],
            [['field' => 2345.123], 1, 'en-US', '2,345.1'],
            [['field' => 2345.173], 1, 'en-US', '2,345.2'],
            [['field' => 2345.123], 4, 'nl-NL', '2.345,1230'],
            [['field' => null], 4, 'nl-NL', null],
            [['field' => 2345], 2, 'en-US', '2,345.00'],
            [['field' => 0], 2, 'en-US', '0.00'],
            [['field' => 0], 3, 'en-US', '0.000'],
            [['field' => 0.000], 2, 'en-US', '0.00'],
            [['field' => 2345.123], "%01.2f", 'en-US', '2345.12'],
            [['field' => 2345.123], "%01.3f", 'en-US', '2345.123'],
            [['field' => 2345.123], "%01.2f", 'nl-NL', '2345,12'],
            [['field' => 2345.123], "%0.1f", 'de-DE', '2345,1'],
            [['field' => 0.123], "%0.1f", 'de-DE', '0,1'],
            [['field' => 0.163], "%0.1f", 'it-IT', '0,2'],
            [['field' => null], "%01.2f", 'nl-NL', null],
            [['field' => 23], [__CLASS__, 'alwaysOne'], 'en-US', 'one'],
            [['field' => null], [__CLASS__, 'alwaysOne'], 'en-US', 'one'],
            ];
    }

    /**
     * @dataProvider provideNumberFormat
     * @param array $row
     * @param mixed $format
     * @param string $locale
     * @param string $output
     * @return void
     * @throws \Zalt\Model\Exception\MetaModelException
     */
    public function testNumberFormat(array $row, mixed $format, string $locale, ?string $output)
    {
        if (! setlocale(LC_ALL, $locale, substr($locale, 0, 2))) {
            return;
        }

        $model     = $this->getModelLoaded([$row]);
        $metaModel = $model->getMetaModel();
        $metaModel->set('field', [
            'numberFormat' => $format,
        ]);

        $bridge    = new DisplayBridge($model);
        $formatter = $bridge->getFormatted('field');
        $this->assertInstanceOf(LateInterface::class, $formatter);

        $bridge->setRow($row);
        $value = Late::rise($formatter);
        $this->assertEquals($output, $value);
    }
}