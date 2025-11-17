<?php

declare(strict_types=1);

/**
 *
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Dependency;

/**
 * Reverse of the Readonly dependency.
 *
 * A class for adding dependencies that turn readonly on in the model unless
 * one of the values the dependency depends on returns a true value.
 *
 * Example:
 * <code>
 * $model->addDependency('CanEditDependency', array('can_edit'), $model->getColNames('label'));
 * </code>
 * Will set readonly=null for all fields with a label when can_edit returns true, otherwise
 * sets readonly=readonly
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
class CanEditDependency extends ReadonlyDependency
{
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
        foreach ($this->_dependentOn as $dependsOn) {
            if ($context[$dependsOn]) {
                return $this->_getUneffecteds();
            }
        }

        return $this->_effecteds;
    }
}
