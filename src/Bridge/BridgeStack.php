<?php

/**
 *
 *
 * @package    Zalt
 * @subpackage Late\Stack
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Zalt\Model\Bridge;

use Zalt\Late\StackInterface;

/**
 * Get an object property get object implementation
 *
 * @package    Zalt
 * @subpackage Late\Stack
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class BridgeStack implements StackInterface
{
    /**
     *
     * @param Object $object
     */
    public function __construct(protected BridgeInterface $bridge)
    { }

    /**
     * Returns a value for $name
     *
     * @param string $name A name indentifying a value in this stack.
     * @return A not late value for $name
     */
    public function lateGet($name)
    {
        return $this->bridge->getLateValue($name);
    }
}
