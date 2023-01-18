<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Dependency;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
class ValueSwitchDependency extends DependencyAbstract
{
    /**
     * When false the effected fields should be recalculated
     *
     * @var bool
     */
    protected bool $_checked_effected = false;

    /**
     *
     * @var array
     */
    protected array $_switches = [];

    /**
     *
     * @param array $switches
     */
    public function __construct(array $switches = null, TranslatorInterface $translate)
    {
        parent::__construct($translate);

        if ($switches) {
            $this->addSwitches($switches);
        }
    }

    /**
     * Recursively refresh effected fields
     *
     * @param array $switches Current level of switches array
     * @param array $dependsOn Current level of $dependsOn array
     * @param array $results The final result, should take the form array(field => array(stting => setting))
     * @throws \Zalt\Model\Dependency\DependencyException
     */
    private function _checkEffectFor(array $switches, array $dependsOn, array &$results)
    {
        if ($dependsOn) {
            array_shift($dependsOn);
            foreach ($switches as $switch) {
                $this->_checkEffectFor($switch, $dependsOn, $results);
            }

            return;
        }

        // At end level when dependsOn is empty
        foreach ($switches as $name => $values) {
            if (! is_array($values)) {
                throw new DependencyException('Incorrect nesting of switches.');
            }

            $keys = array_keys($values);
            $value = array_combine($keys, $keys);

            if (isset($results[$name])) {
                // Can use addition as keys + values should be the same
                $results[$name] = $results[$name] + $value;
            } else {
                $results[$name] = $value;
            }
        }
    }

    /**
     * Refresh the effected fields
     */
    protected function _checkEffected()
    {
        if (! $this->_checked_effected) {
            $results = array();

            $this->_checkEffectFor($this->_switches, $this->_dependentOn, $results);

            // \MUtil\EchoOut\EchoOut::track($results);

            $this->setEffecteds($results);

            $this->_checked_effected = true;
        }
    }


    /**
     * Do a recursive find of the changes
     *
     * @param array $switches Current level of switches array
     * @param array $dependsOn Current level of $dependsOn array
     * @param array $context Context
     * @return array name => array(setting => value)
     */
    protected function _findChanges(array $switches, array $dependsOn, array $context)
    {
        // Found it when depends on is empty
        if (! $dependsOn) {
            return $switches;
        }

        $name = array_shift($dependsOn);

        // When there is no data, return no changes
        if (!array_key_exists($name, $context)) {
//            if (\MUtil\Model::$verbose) {
//                $names = array_diff_key($this->_dependentOn, $context);
//                // \MUtil\EchoOut\EchoOut::r(implode(', ', $names), 'Name(s) not found in ' . get_class($this));
//            }
            return array();
        }
        $value = $context[$name];

        if ($value) {
            // All true cases
            foreach ($switches as $key => $rest) {
                if ($value == $key) {
                    return $this->_findChanges($rest, $dependsOn, $context);
                }
            }
        } else {
            // For non-true value we use exact type comparison, except when both are zero's
            if (null === $value) {
                foreach ($switches as $key => $rest) {
                    if (null === $key) {
                        return $this->_findChanges($rest, $dependsOn, $context);
                    }
                }
            } elseif ((0 === $value) || ("0" === $value)) {
                foreach ($switches as $key => $rest) {
                    if ((0 === $key) || ("0" === $key)) {
                        return $this->_findChanges($rest, $dependsOn, $context);
                    }
                }
            } elseif ("" === $value) {
                foreach ($switches as $key => $rest) {
                    if ("" === $key) {
                        return $this->_findChanges($rest, $dependsOn, $context);
                    }
                }
            }
        }
//        if (\MUtil\Model::$verbose) {
//            \MUtil\EchoOut\EchoOut::track($this->_switches, $this->_dependentOn, $this->_effecteds);
//            \MUtil\EchoOut\EchoOut::r(
//                "Value '$value' not found for field $name among the values: " .
//                implode(', ', array_keys($switches)),
//                'Value not found in ' . get_class($this));
//        }
        return array();
    }

    /**
     * Adds which settings are effected by a value
     *
     * Overrule this function, e.g. when a sub class changed a fixed setting,
     * but for diverse fields.
     *
     * @param string $effectedField A field name
     * @param mixed $effectedSettings A single setting or an array of settings
     * @return DependencyInterface (continuation pattern)
     */
    public function addEffected($effectedField, $effectedSettings): DependencyInterface
    {
        $this->_checked_effected = false;
        $this->_switches[$effectedField] = $effectedSettings;

        return $this;
    }

    /**
     * Recursively merge the new switches into the existing switches
     *
     * @param array $switches The switches
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function addSwitches(array $switches): DependencyInterface
    {
        $this->_checked_effected = false;
        if ($this->_switches) {
            foreach ($switches as $value => $switch) {
                $this->addEffected($value, $switch);
            }
        } else {
            $this->_switches = $switches;
        }

        return $this;
    }

    /**
     * Returns the changes that must be made in an array consisting of
     *
     * <code>
     * array(
     *  field1 => array(setting1 => $value1, setting2 => $value2, ...),
     *  field2 => array(setting3 => $value3, setting4 => $value4, ...),
     * </code>
     *
     * By using [] array notation in the setting name you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array name => array(setting => value)
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $this->_checkEffected();
        return $this->_findChanges($this->getSwitches(), $this->getDependsOn(), $context);
    }

    /**
     * Get the settings for this field effected by this dependency
     *
     * @param string $name Field name
     * @return array of setting => setting of fields with settings for this $name changed by this dependency
     */
    public function getEffected(string $name): array
    {
        $this->_checkEffected();

        return parent::getEffected($name);
    }

    /**
     * Get the fields and their settings effected by by this dependency
     *
     * @return array of name => array(setting => setting) of fields with settings changed by this dependency
     */
    public function getEffecteds(): array
    {
        $this->_checkEffected();

        return parent::getEffecteds();
    }

    /**
     * Get the switches
     *
     * @return array The switches
     */
    public function getSwitches()
    {
        return $this->_switches;
    }
    /**
     * Is this field effected by this dependency?
     *
     * @param $name
     * @return boolean
     */
    public function isEffected($name): bool
    {
        $this->_checkEffected();

        return parent::isEffected($name);
    }

    /**
     * Set the switches
     *
     * @param array $switches The switches
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function setSwitches(array $switches)
    {
        $this->_switches = array();

        return $this->addSwitches($switches);
    }
}
