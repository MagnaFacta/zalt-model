<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Dependency;

use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;

/**
 *
 * @package    Zalt
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
abstract class DependencyAbstract implements DependencyInterface
{
    use TranslateableTrait;
    
    /**
     * Array of setting => setting of setting changed by this dependency
     *
     * The settings array for those effected items that don't have an effects array
     *
     * @var array
     */
    protected array $_defaultEffects = [];

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overridden in sub class, when set to only field names this class will
     * change the array to the correct structure.
     *
     * @var array Of name => name
     */
    protected array $_dependentOn = [];

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overridden in sub class, when set to only field names this class will use _defaultEffects
     * to change the array to the correct structure.
     *
     * @var array of name => array(setting => setting)
     */
    protected array $_effecteds = [];

    /**
     * Set to false to disable automatically setting the onchange code
     *
     * @var boolean
     */
    protected bool $applyOnChange = true;

    /**
     * Set to false to disable automatically setting the onchange code
     *
     * @var string|bool
     */
    protected string|bool $onChangeJs = 'this.form.submit();';

    /**
     * Constructor checks any subclass set variables
     */
    public function __construct(TranslatorInterface $translate)
    {
        // We're setting trait variables so no constructor promotion
        $this->translate = $translate;

        // Make sub class specified dependents confirm to system
        if ($this->_dependentOn) {
            $this->setDependsOn($this->_dependentOn);
        }

        if ($this->_defaultEffects) {
            $this->_defaultEffects = array_combine($this->_defaultEffects, $this->_defaultEffects);
        }

        // Make sub class specified effectds confirm to system
        if ($this->_effecteds) {
            $this->setEffecteds($this->_effecteds);
        }
    }

    /**
     * All string values passed to this function are added as a field the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function addDependsOn($dependsOn): DependencyInterface
    {
        $dependsOn = Ra::flatten(func_get_args());

        foreach ($dependsOn as $dependOn) {
            $this->_dependentOn[$dependOn] = $dependOn;
        }

        return $this;
    }

    /**
     * Adds which settings are effected by a value
     *
     * Overrule this function, e.g. when a sub class changed a fixed setting,
     * but for diverse fields.
     *
     * @param string $effectedField A field name
     * @param mixed $effectedSettings A single setting or an array of settings
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function addEffected($effectedField, $effectedSettings): DependencyInterface
    {
        if ($effectedSettings) {
            foreach ((array) $effectedSettings as $setting) {
                $this->_effecteds[$effectedField][$setting] = $setting;
            }
        } else {
            $this->_effecteds[$effectedField] = [];
        }

        return $this;
    }

    /**
     * Add to the fields effected by this dependency
     *
     * Do not override this function, override addEffected() instead
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public final function addEffecteds(array $effecteds): DependencyInterface
    {
        foreach ($effecteds as $effectedField => $effectedSettings) {
            if (is_int($effectedField) && (! is_array($effectedSettings)) && $this->_defaultEffects) {
                $this->addEffected($effectedSettings, $this->_defaultEffects);
            } else {
                $this->addEffected($effectedField, $effectedSettings);
            }
        }

        return $this;
    }

    /**
     * Use this function for a default application of this dependency to the model
     *
     * @param \Zalt\Model\MetaModelInterface $metaModel Try not to store the model as variabe in the dependency (keep it simple)
     */
    public function applyToModel(MetaModelInterface $metaModel)
    {
        if ($this->applyOnChange) {
            foreach ($this->getDependsOn() as $name) {
                if ($metaModel->is($name, 'elementClass', 'Checkbox')) {
                    if (! $metaModel->has($name, 'onclick')) {
                        $metaModel->set($name, 'onclick', $this->onChangeJs);
                    }
                } else {
                    if (! $metaModel->has($name, 'onchange')) {
                        $metaModel->set($name, 'onchange', $this->onChangeJs);
                    }
                }
            }
        }
    }

    /**
     * Does this dependency depends on this field?
     *
     * @param string $name Field name
     * @return boolean
     */
    public function dependsOn(string $name): bool
    {
        return (bool) isset($this->_dependentOn[$name]);
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
     * /
    public function getChanges(array $context, bool $new = false): array
    {

    }  // */

    /**
     * Return the array of fields this dependecy depends on
     *
     * @return array name => name
     */
    public function getDependsOn(): array
    {
        return $this->_dependentOn;
    }

    /**
     * Get the settings for this field effected by this dependency
     *
     * @param string $name Field name
     * @return array of setting => setting of fields with settings for this $name changed by this dependency
     */
    public function getEffected(string $name): array
    {
        if (isset($this->_effecteds[$name])) {
            return $this->_effecteds[$name];
        }

        return array();
    }

    /**
     * Get the fields and their settings effected by by this dependency
     *
     * @return array of name => array(setting => setting) of fields with settings changed by this dependency
     */
    public function getEffecteds(): array
    {
        return $this->_effecteds;
    }

    /**
     * Is this field effected by this dependency?
     *
     * @param $name
     * @return boolean
     */
    public function isEffected($name): bool
    {
        return isset($this->_effecteds[$name]);
    }

    /**
     * All string values passed to this function are set as the fields the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public function setDependsOn($dependsOn): DependencyInterface
    {
        $this->_dependentOn = array();

        return $this->addDependsOn(func_get_args());
    }

    /**
     * Add to the fields effected by this dependency
     *
     * Do not override this function, override addEffected() instead
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \Zalt\Model\Dependency\DependencyInterface (continuation pattern)
     */
    public final function setEffecteds(array $effecteds): DependencyInterface
    {
        $this->_effecteds = array();

        return $this->addEffecteds($effecteds);
    }
}
