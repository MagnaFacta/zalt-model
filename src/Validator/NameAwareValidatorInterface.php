<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Validator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Validator;

/**
 * Interface to mark validators that want to know the current field name
 *
 * @package    Zalt
 * @subpackage Model\Validator
 * @since      Class available since version 1.0
 */
interface NameAwareValidatorInterface
{
    /**
     * Set the name
     * @param string $name
     * @return void
     */
    public function setName(string $name): void;
}