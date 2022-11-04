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
class MetaModelConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'snippetLoader' => ['directories' => ['Zalt']],
        ];
    }

    public function getDependencies(): array
    {
        return [
            // Legacy MUtil Framework aliases
//            'aliases'    => [
//                \MUtil\Snippets\SnippetLoaderInterface::class => SnippetLoader::class,
//            ],
            'invokables' => [
                MetaModelItemFactory::class => MetaModelItemFactory::class,
            ],
        ];
    }

}