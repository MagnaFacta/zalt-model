<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

use Zalt\Late\Late;
use Zalt\Late\LateCall;
use Zalt\Late\Repeatable;
use Zalt\Late\RepeatableInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Exception\MetaModelException;

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
abstract class BridgeAbstract implements BridgeInterface
{
    /**
     *
     * @var \Zalt\Model\Bridge\BridgeInterface
     */
    protected $_chainedBridge;

    /**
     * @var array $name => [displayFunctions]
     */
    protected $_compilations = [];

    /**
     * Nested array or single row, depending on mode
     *
     * @var mixed array, ArrayObject or false when no row was found
     */
    protected $_data = null;

    /**
     * A lazy repeater
     *
     * @var \Zalt\Late\RepeatableInterface
     */
    protected $_repeater = null;

    /**
     * @var array Optional current url array for links on the page (usable by Zalt/Html/UrlArrayAttribute
     */
    public array $currentUrl = [];

    /**
     * @var string Name use for late stack
     */
    public string $lateStackName = 'bridge';

    /**
     * @var \Zalt\Model\MetaModelInterface
     */
    protected $metaModel;

    /**
     * @var int One of the self::MODE constants
     */
    protected int $mode = BridgeInterface::MODE_LAZY;

    /**
     * @var bool When true we add all data to the Late Stack
     */
    public bool $useAsLateStack = true;

    /**
     * Construct the bridge while setting the model.
     *
     * Extra parameters can be added in subclasses, but the first parameter
     * must remain the model.
     *
     * @param \Zalt\Model\Data\DataReaderInterface $dataModel
     */
    public function __construct(protected DataReaderInterface $dataModel)
    {
        $this->metaModel = $this->dataModel->getMetaModel();
    }

    /**
     * Returns a formatted value or a lazy call to that function,
     * depending on the mode.
     *
     * @param string $name The field name or key name
     * @return mixed Lazy unless in single row mode
     * @throws \Zalt\Model\Exception\MetaModelException
     */
    public function __get(string $name): mixed
    {
        return $this->getFormatted($name);
    }

    /**
     * Checks name for being a key id field and in that case returns the real field name
     *
     * @param string $name The field name or key name
     * @param boolean $throwError By default we throw an error until rendering
     * @return string The real name and not e.g. the key id
     * @throws \Zalt\Model\Exception\MetaModelException
     */
    protected function _checkName(string $name, $throwError = true): string
    {
        if ($this->metaModel->has($name)) {
            return $name;
        }

        $modelKeys = $this->metaModel->getKeys();
        if (isset($modelKeys[$name])) {
            return $modelKeys[$name];
        }

        if ($throwError) {
            throw new MetaModelException(
                sprintf('Request for unknown item %s from model %s.', $name, $this->metaModel->getName())
            );
        }

        return $name;
    }

    /**
     * Return an array of functions used to process the value
     *
     * @param string $name The real name and not e.g. the key id
     * @return array
     */
    abstract protected function _compile(string $name): array;

    protected function _executeCompilation(array $compilations, $value)
    {
        $raw = $value;
        foreach ($compilations as $function) {
            if (is_array($function) && isset($function[2])) {
                // Check if raw should be added to the current callback
                $rawMode = array_pop($function);
                if ($rawMode) {
                    $value = call_user_func($function, $value, $raw);
                    continue;
                }
            }

            $value = call_user_func($function, $value);
        }
        return $value;
    }

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
    public function format($name, $value)
    {
        if (! array_key_exists($name, $this->_compilations)) {
            if ($this->_chainedBridge instanceof BridgeAbstract) {
                $this->_compilations[$name] = array_merge(
                    $this->_chainedBridge->_compile($name),
                    $this->_compile($name)
                );
            } else {
                $this->_compilations[$name] = $this->_compile($name);
            }
        }

        return $this->_executeCompilation($this->_compilations[$name], $value);
    }

    /**
     * Returns a formatted value or a lazy call to that function,
     * depending on the mode.
     *
     * @param string $name The field name or key name
     * @return mixed Lazy unless in single row mode
     * @throws \Zalt\Model\Exception\MetaModelException
     */
    public function getFormatted(string $name): mixed
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        $fieldName = $this->_checkName($name);

        // Make sure the field is in the trackUsage fields list
        $this->metaModel->get($fieldName);

        if ((BridgeInterface::MODE_SINGLE_ROW === $this->mode) && isset($this->_data[$fieldName])) {
            $this->$name = $this->format($fieldName, $this->_data[$fieldName]);
        } else {
            $this->$name = new LateBridgeFormat($this, $fieldName);
        }
        if ($fieldName !== $name) {
            $this->metaModel->get($name);
            $this->$fieldName = $this->$name;
        }

        return $this->$name;
    }

    /**
     * Return the lazy value without any processing.
     *
     * @param string $name The field name or key name
     * @return \Zalt\Late\LateCall
     */
    public function getLate(string $name): ?LateCall
    {
        $name = $this->_checkName($name, false);

        // Make sure data is loaded
        $this->metaModel->get($name);

        return Late::method($this, 'getLateValue', $name);
    }

    /**
     * Get the repeater result for
     *
     * @param string $name The field name or key name
     * @return mixed The result for name
     */
    public function getLateValue(string $name): mixed
    {
        if (! $this->_repeater) {
            $this->getRepeater();
        }

        $current = $this->_repeater->__current();
        if ($current && isset($current[$name])) {
            return $current[$name];
        }

        return null;
    }

    /**
     * Get the mode to one of Lazy (works with any other mode), one single row or multi row mode.
     *
     * @return int On of the MODE_ constants
     */
    public function getMode(): int
    {
        return $this->mode;
    }

    /**
     *
     * @return \Zalt\Model\Data\DataReaderInterface
     */
    public function getModel(): DataReaderInterface
    {
        return $this->dataModel;
    }

    /**
     * Get the repeater source for the lazy data
     *
     * @return \Zalt\Late\RepeatableInterface
     */
    public function getRepeater(): RepeatableInterface
    {
        if (! $this->_repeater) {
            if ($this->_chainedBridge && $this->_chainedBridge->hasRepeater()) {
                $this->setRepeater($this->_chainedBridge->getRepeater());
            } else {
                $this->setRepeater($this->dataModel->loadRepeatable());
            }
        }

        return $this->_repeater;
    }

    /**
     * Switch to single row mode and return that row.
     *
     * @return array, ArrayObject or false when no row was found
     */
    public function getRow(): mixed
    {
        if (null === $this->_data) {
            $this->setRow();
        }

        return $this->_data;
    }

    protected function getUrl(array $params = [])
    {
        return array_merge($this->currentUrl, $params);
    }

    /**
     * Is there a repeater
     *
     * @return bool
     */
    public function hasRepeater(): bool
    {
        return $this->_repeater instanceof RepeatableInterface ||
            ($this->_chainedBridge && $this->_chainedBridge->hasRepeater());
    }

    /**
     * Set the mode to one of Lazy (works with any other mode), one single row or multi row mode.
     *
     * @param int $mode On of the MODE_ constants
     * @return \Zalt\Model\Bridge\BridgeInterface (continuation pattern)
     * @throws \Zalt\Model\Exception\MetaModelException The mode can only be set once
     */
    public function setMode(int $mode): BridgeInterface
    {
        switch ($mode) {
            case BridgeInterface::MODE_LAZY:
            case BridgeInterface::MODE_ROWS:
            case BridgeInterface::MODE_SINGLE_ROW:
                $this->mode = $mode;

                if ($this->_chainedBridge instanceof BridgeAbstract) {
                    $this->_chainedBridge->mode = $this->mode;
                }

                return $this;
        }

        throw new MetaModelException("Illegal bridge mode set after mode had already been set.");
    }

    /**
     * Set the repeater source for the lazy data
     *
     * @param mixed $repeater \MUtil\Lazy\RepeatableInterface or something that can be made into one.
     * @return \Zalt\Model\Bridge\BridgeInterface (continuation pattern)
     */
    public function setRepeater($repeater): BridgeInterface
    {
        if (! $repeater instanceof RepeatableInterface) {
            $repeater = new Repeatable($repeater);
        }
        $this->_repeater = $repeater;
        if ($this->_chainedBridge instanceof BridgeAbstract) {
            $this->_chainedBridge->_repeater = $repeater;
        }
        if ($this->useAsLateStack) {
            Late::addStack($this->lateStackName, new BridgeStack($this));
        }

        return $this;
    }

    /**
     * Switch to single row mode and set that row.
     *
     * @param ?array $row Or load from model
     */
    public function setRow(array $row = null)
    {
        $this->setMode(self::MODE_SINGLE_ROW);

        if (null === $row) {
            // Stop tracking usage, in row mode it is unlikely
            // all fields have been set.
            $this->metaModel->trackUsage(false);
            $row = $this->dataModel->loadFirst();

            if (! $row) {
                $row = array();
            }
        }

        $this->_data = $row;
        if ($this->_chainedBridge instanceof BridgeAbstract) {
            $this->_chainedBridge->_data = $this->_data;
        }

        $this->setRepeater([$this->_data]);
    }
}