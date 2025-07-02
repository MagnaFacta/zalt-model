<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql\Laminas;

use Laminas\Db\Adapter\Adapter;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;


/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class CachedLaminasRunnerFactory
{
    public function __invoke(ContainerInterface $container): CachedLaminasRunner
    {
        $adapter = $container->get(Adapter::class);
        $cache = $container->get(AdapterInterface::class);

        return new CachedLaminasRunner($adapter, $cache);
    }
}
