<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

use Zalt\Model\Bridge\DisplayBridge;
use Zalt\Model\Bridge\Laminas\LaminasFilterBridge;
use Zalt\Model\Bridge\Laminas\LaminasValidatorBridge;
use Zalt\Snippets\ModelBridge\DetailTableBridge;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\Snippets\ModelBridge\ZendFormBridge;

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class MetaModelConfigProvider
{
    public function __invoke(): array
    {
        return [
            'config'       => self::getConfig(),
            'dependencies' => self::getDependencies(),
        ];
    }
    
    public static function getBridges(): array
    {
        return [
            'display'   => DisplayBridge::class,
            'filter'    => LaminasFilterBridge::class,
            'form'      => ZendFormBridge::class,
            'itemTable' => DetailTableBridge::class,
            'table'     => TableBridge::class,
            'validator' => LaminasValidatorBridge::class,
        ]; 
    }

    public static function getConfig(): array
    {
        return [
            'bridges' => self::getBridges(),
            'translateDatabaseFields' => true,
        ];
    }
    
    public static function getDependencies(): array
    {
        return [
            'factories'  => [
                MetaModelLoader::class => MetaModelLoaderFactory::class,
            ],
        ];
    }

}