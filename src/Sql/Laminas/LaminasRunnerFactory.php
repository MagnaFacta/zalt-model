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


/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class LaminasRunnerFactory
{
    public function __invoke(ContainerInterface $container): LaminasRunner
    {
        $adapter = $container->get(Adapter::class);

        return new LaminasRunner($adapter);
    }
}