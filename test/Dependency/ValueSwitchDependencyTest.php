<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Dependency;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Mock\MockTranslator;
use Zalt\Model\MetaModelTestTrait;
use Zalt\Model\Ra\PhpArrayModel;

/**
 * @package    Zalt
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
class ValueSwitchDependencyTest extends TestCase
{
    use MetaModelTestTrait;

    public function getModelLoaded(array $rows): PhpArrayModel
    {
        $sm = $this->getServiceManager();
        if (! $sm->has(TranslatorInterface::class)) {
            $sm->set(TranslatorInterface::class, new MockTranslator());
        }

        $loader = $this->getModelLoader();

        $data  = new \ArrayObject($rows);
        return $loader->createModel(PhpArrayModel::class, 'test', $data);
    }

    public function getRows(): array
    {
        return [
            0 => ['a' => 'A1', 'b' => 'B1', 'c' => 20],
            1 => ['a' => 'A2', 'b' => 'B2', 'c' => 40],
            2 => ['a' => 'A3', 'b' => 'C3', 'c' => 10],
            3 => ['a' => 'A4', 'b' => 'D4', 'c' => 30],
        ];
    }

    public function getSwitchesForRows(array $rows, array $options)
    {
        $switches = [];
        foreach ($rows as $row) {
            $newOptions = $options;
            $newOptions[$row['a']] = $row['a'];
            $switches[$row['c']]['b']['multiOptions'] = $newOptions;
        }

        return $switches;
    }

    public function testLoadDependency1(): void
    {
        $model = $this->getModelLoaded([]);
        $metaModel = $model->getMetaModel();

        $this->assertFalse($metaModel->hasDependencies());

        $key = $metaModel->addDependency([ValueSwitchDependency::class, []]);
        $this->assertTrue($metaModel->hasDependencies());
    }

    public function testLoadDependency2(): void
    {
        $loader  = $this->getModelLoader();
        $rows    = $this->getRows();
        $options = array_column($rows, 'b', 'b');

        $valueSwitcher = $loader->createDependency(ValueSwitchDependency::class, $this->getSwitchesForRows($rows, $options));
        $this->assertInstanceOf(ValueSwitchDependency::class, $valueSwitcher);
        $valueSwitcher->setSwitches($this->getSwitchesForRows($rows, $options));

        $model = $this->getModelLoaded($rows);
        $metaModel = $model->getMetaModel();

        $this->assertFalse($metaModel->hasDependencies());

        $metaModel->addDependency($valueSwitcher);
        $this->assertTrue($metaModel->hasDependencies());
    }

    public function XXtestLoadingWith()
    {
        $rows      = $this->getRows();
        $model     = $this->getModelLoaded($rows);
        $metaModel = $model->getMetaModel();
        $options   = array_column($rows, 'b', 'b');

        $metaModel->set('a', ['label' => 'A field']);
        $metaModel->set('b', [
            'label' => 'B field',
            'multiOptions' => $options,
        ]);
        $metaModel->set('c', ['label' => 'C field']);

        $this->assertTrue($metaModel->has('b', 'multiOptions'));
        $this->assertEquals($options, $metaModel->get('b', 'multiOptions'));

        $key = $metaModel->addDependency([ValueSwitchDependency::class, $this->getSwitchesForRows($rows, $options)], 'c');

        $this->assertEquals($options, $metaModel->get('b', 'multiOptions'));
        $dependencies = $metaModel->getDependencies([ValueSwitchDependency::class, $this->getSwitchesForRows($rows, $options)], 'b');
        $this->assertCount(1, $dependencies);

        $valueSwitcher = $dependencies[$key];
        $this->assertInstanceOf(DependencyInterface::class, $valueSwitcher);
        $this->assertInstanceOf(ValueSwitchDependency::class, $valueSwitcher);
        $this->assertEquals(['c' => 'c'], $valueSwitcher->getDependsOn());
        $this->assertEquals(['b' => ['multiOptions' => 'multiOptions']], $valueSwitcher->getEffecteds());

        $this->assertEquals($options, $metaModel->get('b', 'multiOptions'));

        foreach ($rows as $row) {
            $model->loadFirst(['a' => $row['a']]);
            $newOptions = $options;
            $newOptions[$row['a']] = $row['a'];
            $this->assertEquals($newOptions, $metaModel->get('b', 'multiOptions'));
        }
    }

    public function testSettingWith()
    {
        $loader  = $this->getModelLoader();
        $rows    = $this->getRows();
        $options = array_column($rows, 'b', 'b');

        $valueSwitcher = $loader->createDependency(ValueSwitchDependency::class, $this->getSwitchesForRows($rows, $options));
        $this->assertInstanceOf(ValueSwitchDependency::class, $valueSwitcher);
        $valueSwitcher->setSwitches($this->getSwitchesForRows($rows, $options));
        $valueSwitcher->setDependsOn('c');
        $this->assertEquals(['c' => 'c'], $valueSwitcher->getDependsOn());
        $this->assertEquals(['b' => ['multiOptions' => 'multiOptions']], $valueSwitcher->getEffecteds());


        $model     = $this->getModelLoaded($rows);
        $metaModel = $model->getMetaModel();

        $metaModel->set('a', ['label' => 'A field']);
        $metaModel->set('b', [
            'label' => 'B field',
            'multiOptions' => $options,
            ]);
        $metaModel->set('c', ['label' => 'C field']);

        $this->assertTrue($metaModel->has('b', 'multiOptions'));
        $this->assertEquals($options, $metaModel->get('b', 'multiOptions'));

        $key = $metaModel->addDependency($valueSwitcher, 'c');
        $this->assertEquals($options, $metaModel->get('b', 'multiOptions'));
        $dependencies = $metaModel->getDependencies('b');
        $this->assertCount(1, $dependencies);

        $dependency = $dependencies[$key];
        $this->assertInstanceOf(DependencyInterface::class, $dependency);
        $this->assertInstanceOf(ValueSwitchDependency::class, $dependency);

        $this->assertEquals($options, $metaModel->get('b', 'multiOptions'));

        foreach ($rows as $row) {
            $model->loadFirst(['a' => $row['a']]);
            $newOptions = $options;
            $newOptions[$row['a']] = $row['a'];
            $this->assertEquals($newOptions, $metaModel->get('b', 'multiOptions'));
        }
    }
}