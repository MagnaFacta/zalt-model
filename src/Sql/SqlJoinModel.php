<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class SqlJoinModel
{
    public function __construct(
        protected string $fromTableName,
        protected SqlRunnerInterface $sqlRunner
    ) { }
}