<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

use Laminas\Filter\FilterInterface;

/**
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
interface FilterBridgeInterface extends BridgeInterface
{
    /**
     * Retrieve all validators for an element
     *
     * @param string $name
     * @return FilterInterface[]
     */
    public function getFiltersFor(string $name): array;

    /**
     * Set the element class validator compiler for a certain element class.
     *
     * @param string $elementClassName
     * @param callable $callable with parameters (MetaModel, name) return an (empty) array of validator
     * @return FilterBridgeInterface (Continuation pattern)
     */
    public function setElementClassCompiler(string $elementClassName, callable $callable): FilterBridgeInterface;

    /**
     * Set the type class validator compiler for a certain element class.
     *
     * @param int $typeId
     * @param callable $callable with parameters (MetaModel, name) return an (empty) array of validator
     * @return FilterBridgeInterface (Continuation pattern)
     */
    public function setTypeClassCompiler(int $typeId, callable $callable): FilterBridgeInterface;
}