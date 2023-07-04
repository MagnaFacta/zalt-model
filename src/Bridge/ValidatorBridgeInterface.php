<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

/**
 * Validator bridges create arrays of validators for meta model elements, using these settings
 *
 * - validator: adds this validator
 * - validators[]: adds an array of multiple validators
 * - ignoreElementValidators: Skip the validators from the ElementClassCompiler
 * - ignoreTypeValidators: Skip the validators from the TypeClassCompiler
 *
 * What a validator consists of (or what can be created as one) depends on the implementing class.
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
interface ValidatorBridgeInterface extends BridgeInterface
{
    /**
     * Retrieve all validators for an element
     *
     * @param string $name
     * @return array
     */
    public function getValidatorsFor(string $name): array;

    /**
     * Set the element class validator compiler for a certain element class.
     *
     * @param string $elementClassName
     * @param callable $callable with parameters (MetaModel, name) return an (empty) array of validator
     * @return ValidatorBridgeInterface (Continuation pattern)
     */
    public function setElementClassCompiler(string $elementClassName, callable $callable): ValidatorBridgeInterface;

    /**
     * Set the type class validator compiler for a certain element class.
     *
     * @param int $typeId
     * @param callable $callable with parameters (MetaModel, name) return an (empty) array of validator
     * @return ValidatorBridgeInterface (Continuation pattern)
     */
    public function setTypeClassCompiler(int $typeId, callable $callable): ValidatorBridgeInterface;
}