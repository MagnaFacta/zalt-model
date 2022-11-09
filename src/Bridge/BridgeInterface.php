<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

use Zalt\Late\RepeatableInterface;
use Zalt\Model\Data\DataReaderInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
interface BridgeInterface
{
    /**
     * Mode when all output is lazy until rendering
     */
    const MODE_LAZY = 0;

    /**
     * Mode when all rows are preloaded using model->load()
     */
    const MODE_ROWS = 1;

    /**
     * Mode when only a single row is loaded using model->loadFirst()
     */
    const MODE_SINGLE_ROW = 2;

    /**
     * Construct the bridge while setting the model.
     *
     * Extra parameters can be added in subclasses, but the first parameter
     * must remain the model.
     *
     * @param \Zalt\Model\Data\DataReaderInterface $model
     */
    public function __construct(DataReaderInterface $model);

    /**
     * Returns a formatted value or a lazy call to that function,
     * depending on the mode.
     *
     * @param string $name The field name or key name
     * @return mixed Lazy unless in single row mode
     * @throws \MUtil\Model\ModelException
     */
    public function __get(string $name): mixed;

    /**
     * Return an array of functions used to process the value
     *
     * @param string $name The real name and not e.g. the key id
     * @return array
     */
    // protected function _compile(string $name): array;

    /**
     * Format a value using the rules for the specified name.
     *
     * This is the workhouse function for the foematter and can
     * also be used with data not loaded from the model.
     *
     * To add the raw value to the called function as raw parameter, use an array callback for function,
     * and add a temporary third value of true.
     *
     * @param string $name The real name and not e.g. the key id
     * @param mixed $value
     * @return mixed
     */
    public function format($name, $value);

    /**
     * Returns a formatted value or a lazy call to that function,
     * depending on the mode.
     *
     * @param string $name The field name or key name
     * @return mixed Lazy unless in single row mode
     * @throws \Zalt\Model\Exceptions\MetaModelException
     */
    public function getFormatted($name): mixed;

    /**
     * Get the mode to one of Lazy (works with any other mode), one single row or multi row mode.
     *
     * @return int On of the MODE_ constants
     */
    public function getMode(): int;

    /**
     *
     * @return \Zalt\Model\Data\DataReaderInterface
     */
    public function getModel(): DataReaderInterface;

    /**
     * Get the repeater source for the lazy data
     *
     * @return \Zalt\Late\RepeatableInterface
     */
    public function getRepeater(): RepeatableInterface;

    /**
     * Is there a repeater
     *
     * @return bool
     */
    public function hasRepeater(): bool;

    /**
     * Set the mode to one of Lazy (works with any other mode), one single row or multi row mode.
     *
     * @param int $mode On of the MODE_ constants
     * @return \Zalt\Model\Bridge\BridgeInterface (continuation pattern)
     * @throws \Zalt\Model\Exceptions\MetaModelException The mode can only be set once
     */
    public function setMode(int $mode): BridgeInterface;
    
    /**
     * Set the repeater source for the lazy data
     *
     * @param mixed $repeater \MUtil\Lazy\RepeatableInterface or something that can be made into one.
     * @return \Zalt\Model\Bridge\BridgeInterface (continuation pattern)
     */
    public function setRepeater($repeater): BridgeInterface;
}