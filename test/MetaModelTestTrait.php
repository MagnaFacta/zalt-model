<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

use Zalt\Loader\ProjectOverloader;
use Zalt\Loader\ProjectOverloaderFactory;
use Zalt\Mock\SimpleServiceManager;

/**
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
trait MetaModelTestTrait
{
    public array $serverManagerConfig = [];

    public function getModelLoader(): MetaModelLoader
    {
        static $loader;

        if ($loader instanceof MetaModelLoader) {
            return $loader;
        }

        $sm = $this->getServiceManager();
        $overFc = new ProjectOverloaderFactory();
        $sm->set(ProjectOverloader::class, $overFc($sm));

        $mmlf   = new MetaModelLoaderFactory();
        $loader = $mmlf($sm);

        return $loader;
    }

    public function getServiceManager(): SimpleServiceManager
    {
        static $sm;

        if (! $sm instanceof SimpleServiceManager) {
            $sm = new SimpleServiceManager(['config' => $this->serverManagerConfig]);
        }

        return $sm;
    }
}