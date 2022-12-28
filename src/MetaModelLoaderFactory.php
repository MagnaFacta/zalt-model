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
        $overloader = $container->get(ProjectOverloader::class);

        $output = new MetaModelLoader($overloader->createSubFolderOverloader('Model'));

        // Preparing the other parts
//        if (! Html::hasSnippetLoader()) {
//            Html::setSnippetLoader($output);
//        }

        return $output;
    }
}