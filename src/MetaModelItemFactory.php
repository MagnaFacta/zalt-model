<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class MetaModelItemFactory
{
    public function __invoke(ContainerInterface $container): SnippetLoader
    {
        $config = $container->get('config');
        if (isset($config['overLoaderPaths'])) {
            $dirs = (array) $config['overLoaderPaths'];
        } else {
            $dirs = ['Zalt'];
        }

        $output = new SnippetLoader($container, $dirs);

//        if (! Html::hasSnippetLoader()) {
//            Html::setSnippetLoader($output);
//        }

        return $output;
    }

}