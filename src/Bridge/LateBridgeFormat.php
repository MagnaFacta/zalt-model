<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

use Zalt\Late\StackInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
class LateBridgeFormat extends \Zalt\Late\LateAbstract
{
    /**
     *
     * @var \Zalt\Late\RepeatableInterface
     */
    protected $repeater;

    /**
     *
     * @param BridgeInterface $bridge
     * @param string $fieldName
     */
    public function __construct(protected BridgeInterface $bridge, protected string $fieldName)
    { }

    /**
     * The functions that fixes and returns a value.
     *
     * Be warned: this function may return a lazy value.
     *
     * @param \Zalt\Late\StackInterface $stack A stack object providing variable data
     * @return mixed
     */
    public function __toValue(StackInterface $stack): mixed
    {
        if (! $this->repeater) {
            $this->repeater = $this->bridge->getRepeater();
        }

        $out     = null;
        $current = $this->repeater->__current();
        if ($current) {
            if (isset($current->{$this->fieldName})) {
                $out = $current->{$this->fieldName};
            }
        }
        return $this->bridge->format($this->fieldName, $out);
    }
}