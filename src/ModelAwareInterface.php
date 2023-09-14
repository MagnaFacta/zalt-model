<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

use Zalt\Model\Data\FullDataInterface;

/**
 * Interface to mark validators that use a data model
 *
 * @package    Zalt
 * @subpackage Model\Validate
 * @since      Class available since version 1.0
 */
interface ModelAwareInterface
{
    /**
     * Set / apply the model
     * @param FullDataInterface $model
     * @return void
     */
    public function setDataModel(FullDataInterface $model): void;
}