<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Dependency;

use \Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
interface DependencyInterface
{
    /**
     * All string values passed to this function are added as a field the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function addDependsOn($dependsOn): DependencyInterface;

    /**
     * Adds which settings are effected by a value
     *
     *
     * @param string $effectedField A field name
     * @param mixed $effectedSettings A single setting or an array of settings
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function addEffected($effectedField, $effectedSettings): DependencyInterface;

    /**
     * Add to the fields effected by this dependency
     *
     * Do not override this function, override addEffected() instead
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function addEffecteds(array $effecteds): DependencyInterface;

    /**
     * Use this function for a default application of this dependency to the model
     *
     * @param \Zalt\Model\MetaModelInterface $model Try not to store the model as variabe in the dependency (keep it simple)
     */
    public function applyToModel(MetaModelInterface $model);

    /**
     * Does this dependency depends on this field?
     *
     * @param string $name Field name
     * @return boolean
     */
    public function dependsOn(string $name): bool;

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
    public function getChanges(array $context, bool $new = false): array;

    /**
     * Return the array of fields this dependecy depends on
     *
     * @return array name => name
     */
    public function getDependsOn(): array;

    /**
     * Get the settings for this field effected by this dependency
     *
     * @param string $name Field name
     * @return array of setting => setting of fields with settings for this $name changed by this dependency
     */
    public function getEffected(string $name): array;

    /**
     * Get the fields and their settings effected by by this dependency
     *
     * @return array of name => array(setting => setting) of fields with settings changed by this dependency
     */
    public function getEffecteds(): array;

    /**
     * Is this field effected by this dependency?
     *
     * @param string $name
     * @return bool
     */
    public function isEffected(string $name): bool;

    /**
     * All string values passed to this function are set as the fields the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function setDependsOn($dependsOn): DependencyInterface;

    /**
     * Add to the fields effected by this dependency
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function setEffecteds(array $effecteds): DependencyInterface;
}