<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

use Psr\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class MetaModelLoaderFactory
{
    public function __invoke(ContainerInterface $container): MetaModelLoader
    {
        $config     = $this->checkConfig($container->get('config'));
        $overloader = $container->get(ProjectOverloader::class);

        return new MetaModelLoader($overloader->createSubFolderOverloader('Model'), $config['model']);
    }
    
    public function checkConfig(array $config): array
    {
        if (! isset($config['model']['bridges'])) {
            $config['model']['bridges'] = MetaModelConfigProvider::getBridges();
        }
        if (! isset($config['model']['linkedDefaults'])) {
            $config['model']['linkedDefaults'] = [];
        }
        if (! isset($config['model']['linkedDefaults']['type'])) {
            $config['model']['linkedDefaults']['type'] = $config['locale']['defaultTypes'];
        }
        return $config;
    }
}