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
        $config     = $container->get('config');
        $overloader = $container->get(ProjectOverloader::class);

        if (! isset($config['model']['bridges'])) {
            $config['model']['bridges'] = MetaModelConfigProvider::getBridges();
        }

        $output = new MetaModelLoader($overloader->createSubFolderOverloader('Model'), $config['model']);

        // Preparing the other parts
//        if (! Html::hasSnippetLoader()) {
//            Html::setSnippetLoader($output);
//        }

        return $output;
    }
}