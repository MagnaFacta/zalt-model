<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

/**
 * A marker interface to allow some types to overwrite existing settings
 *
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
interface OverwritingTypeInterface extends ModelTypeInterface
{

}