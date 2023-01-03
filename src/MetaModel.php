<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

use Zalt\Late\Late;
use Zalt\Late\LateInterface;
use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Dependency\DependencyInterface;
use Zalt\Model\Exception\MetaModelException;
use Zalt\Model\Transformer\ModelTransformerInterface;
use Zalt\Model\Transformer\NestedTransformer;
use Zalt\Ra\Ra;

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
class MetaModel implements MetaModelInterface
{
    /**
     * Identifier fro alias fields
     */
    const ALIAS_OF  = 'alias_of';

    /**
     * Identifier for auto save fields
     */
    const AUTO_SAVE = 'auto_save';

    /**
     * Identifier for the load transformers
     */
    const LOAD_TRANSFORMER = 'load_transformer';

    /**
     * Identifier for the save transformers
     */
    const SAVE_TRANSFORMER = 'save_transformer';

    /**
     * Identifier for save when test fields
     */
    const SAVE_WHEN_TEST   = 'save_when_test';

    /**
     * Array containing the names of the key fields of the model
     *
     * @var array int => name
     */
    private $_keys;

    private array $_maps = [];

    /**
     * Contains the per field settings of the model
     *
     * @var array field_name => array(settings)
     */
    private $_model = array();

    /**
     * Do we use the dependencies?
     *
     * @var boolean
     */
    private $_model_enable_dependencies = true;

    /**
     * Contains the settings for the model as a whole
     *
     * @var array
     */
    private $_model_meta = array();

    /**
     * Dependencies that transform the model
     *
     * @var array order => \Zalt\Model\Dependency\DependencyInterface
     */
    private $_model_dependencies = array();

    /**
     * The order in which field names where ->set() since
     * the last ->resetOrder() - minus those not set.
     *
     * @var array
     */
    private $_model_order;

    /**
     * Contains the (order in which) fields where accessed using
     * ->get(), containing only those fields that where accesed.
     *
     * @var bool|array
     */
    private $_model_used = false;

    /**
     *
     * @var array of ModelTransformerInterface
     */
    private $_transformers = array();

    /**
     * The increment for item ordering, default is 10
     *
     * @var int
     */
    public $orderIncrement = 10;

    /**
     *
     * @param string $modelName Hopefully unique model name, used for joining models and sub forms, etc...
     */
    public function __construct(
        private string $modelName, 
        protected array $linkedDefaults,
        protected MetaModelLoader $modelLoader,
    )
    { }

    protected function _getKeyValue($name, $key)
    {
        if (isset($this->_model[$name][$key])) {
            $value = $this->_model[$name][$key];

            if ($value instanceof LateInterface) {
                $value = Late::rise($value);
            }

            return $value;
        }
        if ($name = $this->getAlias($name)) {
            return $this->_getKeyValue($name, $key);
        }

        return null;
    }

    /**
     * Add a dependency where the value in one field can change settings for the other field
     *
     * Dependencies are processed in the order they are added
     *
     * @param mixed $dependency DependencyInterface or string or array to create one
     * @param mixed $dependsOn Optional string field name or array of fields that do the changing
     * @param array $effects Optional array of field => array(setting) of settings are changed, array of whatever
     * the dependency accepts as an addEffects() argument
     * @param mixed $key A key to identify the specific dependency.
     * @return int The actual key used.
     */
    public function addDependency($dependency, $dependsOn = null, array $effects = null,  $key = null)
    {
        if (! $dependency instanceof DependencyInterface) {
            $loader = \MUtil\Model::getDependencyLoader();

            if (is_array($dependency)) {
                $parameters = $dependency;
                $className  = array_shift($parameters);
            } else {
                $parameters = array();
                $className  = (string) $dependency;
            }

            $dependency = $loader->create($className, ...$parameters);
        }
        if (null !== $dependsOn) {
            $dependency->addDependsOn($dependsOn);
        }
        if (is_array($effects)) {
            $dependency->addEffecteds($effects);
        }

        if (null === $key) {
            $keys = array_filter(array_keys($this->_model_dependencies), 'is_int');

            $key = ($keys ? max($keys) : 0) + 10;
        }

        $dependency->applyToModel($this);

        $this->_model_dependencies[$key] = $dependency;

        return $key;
    }

    /**
     * @param string $alias Alternative to map to
     * @param string $fieldName Existing field
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function addMap(string $alias, string $fieldName): MetaModelInterface
    {
        $this->_maps[$alias] = $fieldName;

        return $this;
    }

    /**
     * Add a 'submodel' field to the model.
     *
     * You get a nested join where a set of rows is placed in the $name field
     * of each row of the parent model.
     *
     * @param DataReaderInterface $model
     * @param array $joins The join fields for the sub model
     * @param string $name Optional 'field' name, otherwise model name is used
     * @return NestedTransformer The added transformer
     */
    public function addModel(DataReaderInterface $model, array $joins, $name = null)
    {
        if (null === $name) {
            $name = $model->getName();
        }

        $trans = new NestedTransformer();
        $trans->addModel($model, $joins);

        $this->addTransformer($trans);
        $this->set($name,
                   'model', $model,
                   'elementClass', 'FormTable',
                   'type', MetaModelInterface::TYPE_CHILD_MODEL
        );

        return $trans;
    }

    /**
     * Add a model transformer
     *
     * @param ModelTransformerInterface $transformer
     * @return MetaModelInterface (continuation pattern)
     */
    public function addTransformer(ModelTransformerInterface $transformer): MetaModelInterface
    {
        foreach ($transformer->getFieldInfo($this) as $name => $info) {
            $this->set($name, $info);
        }
        $this->_transformers[] = $transformer;
        return $this;
    }

    /**
     * Recursively apply the changes from a dependency
     *
     * @param MetaModelInterface $model
     * @param array $changes
     * @param array $data Referenced data
     */
    public function applyDependencyChanges(MetaModelInterface $model, array $changes, array &$data)
    {
        // Here we could allow only those changes this dependency claims to change
        // or even check all of them are set.
        foreach ($changes as $name => $settings) {
            if (isset($settings['model'])) {
                $submodel = $model->get($name, 'model');
                if ($submodel instanceof DataReaderInterface) {
                    if (! isset($data[$name])) {
                        $data[$name] = array();
                    }

                    foreach ($data[$name] as &$row) {
                        $submodel->applyDependencyChanges($submodel, $settings['model'], $row);
                    }
                }

                unset($settings['model']);
            }

            $model->set($name, $settings);

            // Change the actual value
            If (isset($settings['value'])) {
                $data[$name] = $settings['value'];
            }
        }
    }

    /**
     * Remove all non-used elements from a form by setting the elementClasses to None.
     *
     * Checks for dependencies and keys to be included
     *
     * @return MetaModelInterface (continuation pattern)
     */
    public function clearElementClasses()
    {
        $labels  = $this->getColNames('label');
        $options = array_intersect($this->getColNames('multiOptions'), $labels);

        // Set element class to text for those with labels without an element class
        $this->setDefault($options, 'elementClass', 'Select');

        // Set element class to text for those with labels without an element class
        $this->setDefault($labels, 'elementClass', 'Text');

        // Hide al dependencies plus the keys
        $elems   = $this->getColNames('elementClass');
        $depends = $this->getDependentOn($elems) + $this->getKeys();
        if ($depends) {
            $this->setDefault($depends, 'elementClass', 'Hidden');
        }

        // Leave out the rest
        $this->setDefault('elementClass', 'None');

        // Cascade
        foreach ($this->getCol('model') as $subModel) {
            if ($subModel instanceof DataReaderInterface) {
                $subModel->clearElementClasses();
            }
        }

        return $this;
    }

    /**
     * Delete all, one or some values for a certain field name.
     *
     * @param string $name Field name
     * @param string|array|null $arrayOrKey1 Null or the name of a single attribute or an array of attribute names
     * @param string $key2 Optional a second attribute name.
     */
    public function del($name, $arrayOrKey1 = null, $key2 = null)
    {
        if (func_num_args() == 1) {
            unset($this->_model[$name], $this->_model_order[$name]);
            if ($this->_model_used) {
                unset($this->_model_used[$name]);
            }
        } else {
            $args = func_get_args();
            array_shift($args);
            $args = Ra::flatten($args);

            foreach ($args as $arg) {
                unset($this->_model[$name][$arg]);
            }
        }
    }

    /**
     * Disable the onload settings. This is sometimes needed for speed/
     *
     * @return MetaModelInterface (continuation pattern)
     */
    public function disableOnLoad()
    {
        $this->setMeta(self::LOAD_TRANSFORMER, false);

        return $this;
    }

    /**
     * Gets one or more values for a certain field name.
     *
     * Usage example, with these values set:
     * <code>
     * $this->set('field_x', 'label', 'label_x', 'size', 100, 'maxlength', 120, 'xyz', null);
     * </code>
     *
     * Retrieve one attribute:
     * <code>
     * $label = $this->get('field_x', 'label');
     * </code>
     * Returns the label 'label_x'
     *
     * Retrieve another attribute:
     * <code>
     * $label = $this->get('field_x', 'xyz');
     * </code>
     * Returns null
     *
     * Retrieve all attributes:
     * <code>
     * $fieldx = $this->get('field_x');
     * </code>
     * Returns array('label' => 'label_x', 'size' => 100, 'maxlength' => 120)
     * Note: null value 'xyz' is not returned.
     *
     * Two options for retrieving specific attributes:
     * <code>
     * $list = $this->get('field_x', 'label', 'size', 'xyz');
     * $list = $this->get('field_x' array('label', 'size', 'xyz'));
     * </code>
     * Both return array('label' => 'label_x', 'size' => 100)
     * Note: null value 'xyz' is not returned.
     *
     * @param string $name Field name
     * @param string|array|null $arrayOrKey1 Null or the name of a single attribute or an array of attribute names
     * @param string $key2 Optional a second attribute name.
     * @return mixed
     */
    public function get($name, $arrayOrKey1 = null, $key2 = null)
    {
        $args = func_get_args();
        $args = Ra::args($args, 1);

        if ($this->_model_used && (! isset($this->_model_used[$name]))) {
            $this->_model_used[$name] = $name;
        }

        switch (count($args)) {
            case 0:
                if (isset($this->_model[$name])) {
                    if ($this->_model[$name] instanceof LateInterface) {
                        $result = Late::rise($this->_model[$name]);
                    } else {
                        $result = $this->_model[$name];
                    }
                    if ($alias = $this->getAlias($name)) {
                        $result = $result + $this->get($alias);
                    }
                    return $result;
                } else {
                    return array();
                }

            case 1:
                return $this->_getKeyValue($name, reset($args));

            default:
                $results = array();
                foreach ($args as $key) {
                    $value = $this->_getKeyValue($name, $key);

                    if (null !== $value) {
                        $results[$key] = $value;
                    }
                }
                return $results;
        }
    }

    /**
     * Returns the field that name is an Alias of
     *
     * @param string $name
     * @return string
     */
    public function getAlias($name)
    {
        if (isset($this->_model[$name][self::ALIAS_OF])) {
            return $this->_model[$name][self::ALIAS_OF];
        }
    }

    /**
     * Create the bridge for the specific idenitifier
     *
     * This will always be a new bridge because otherwise you get
     * instabilities as bridge objects are shared without knowledge
     *
     * @param DataReaderInterface $dataModel
     * @param string $identifier
     * @param array $args Optional extra arguments
     * @return \Zalt\Model\Bridge\BridgeInterface
     */
    public function getBridgeForModel(DataReaderInterface $dataModel, $identifier, ...$parameters): BridgeInterface
    {
        return $this->modelLoader->createBridge($identifier, $dataModel, ...$parameters);
    }

    /**
     * Get an array of field names with the value of a certain attribute if set.
     *
     * Example:
     * <code>
     * $this->getCol('label');
     * </code>
     * returns an array of labels set with the field name as key.
     *
     * @param string $columnName Name of the attribute
     * @return array name -> value
     */
    public function getCol($columnName)
    {
        $results = array();

        foreach ($this->_model as $name => $row) {
            if ($this->has($name, $columnName)) {
                $results[$name] = $this->get($name, $columnName);
            }
        }

        return $results;
    }

    /**
     * Get an array of field names, but only when the value of a certain attribute is set.
     *
     * Example:
     * <code>
     * $this->getCol('label');
     * </code>
     * returns an array of field names where the label is set
     *
     * This is a more efficient function than using array_keys($tmoel->getCol())
     *
     * @param string $column_name Name of the attribute
     * @return array [names]
     */
    public function getColNames($columnName)
    {
        $results = array();

        foreach ($this->_model as $name => $row) {
            if ($this->has($name, $columnName)) {
                $results[] = $name;
            }
        }

        return $results;
    }

    /**
     * Get the dependencies this name has a dependency to at all or on the specific setting
     *
     * @param mixed $name Field name or array of fields
     * @param string $setting Setting name
     * @return array of Dependencies
     */
    public function getDependencies($name, $setting = null)
    {
        $names   = (array) $name;
        $results = array();

        foreach ($this->_model_dependencies as $key => $dependency) {
            if ($dependency instanceof DependencyInterface) {
                foreach ($names as $name) {
                    $settings = $dependency->getEffected($name);

                    if ($settings) {
                        if ((null === $setting) or isset($settings[$setting])) {
                            $results[$key] = $dependency;
                            continue;
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get the names of the fields of the dependencies this name has a dependency
     * to at all or on the specific setting
     *
     * @param mixed $name Field name or array of fields
     * @param string $setting Setting name
     * @return array of name => name
     */
    public function getDependentOn($name, $setting = null)
    {
        $dependencies = $this->getDependencies($name, $setting);
        $results      = array();

        foreach ($dependencies as $dependency) {
            if ($dependency instanceof DependencyInterface) {
                $results = $results + $dependency->getDependsOn();
            }
        }

        return $results;
    }

    /**
     * Returns all the field names in this model
     *
     * @return array Of names
     */
    public function getItemNames()
    {
        return array_keys($this->_model);
    }

    /**
     * Returns all the field names that have the properties passed in the parameters
     *
     * @param array ...$args A single key value array or a sequence of items made into an array using Ra::pairs()
     * @return array Of names
     */
    public function getItemsFor(...$args)
    {
        $results = [];
        $pairs = Ra::pairs($args);

        foreach ($this->_model as $itemName => $row) {
            $found = true;

            foreach ($pairs as $paramName => $value) {
                if (! $this->is($itemName, $paramName, $value)) {
                    $found = false;
                    break;
                }
            }
            if ($found) {
                $results[] = $itemName;
            }
        }

        return $results;
    }

    /**
     * Get an array of items using a previously set custom ordering
     *
     * When two items have the same order value, they both will be included in the resultset
     * but ordering is unpredictable. Fields without an explicitly set order value will be
     * added with increments of $this->orderIncrement (default = 10)
     *
     * Use <code>$this->set('fieldname', 'order', <value>);</code> to set a custom ordering.
     *
     * @see set()
     * @return array int => name
     */
    public function getItemsOrdered()
    {
        $order = (array) $this->_model_order;
        asort($order);
        $result = array_keys($order);
        foreach($this->_model as $field => $element) {
            if (! array_key_exists($field, $order)) {
                $result[] = $field;
            }
        }
        return $result;
    }

    /**
     * The names of the items called using get() since the last
     * call to trackUsage(true).
     *
     * @return array name => name
     */
    public function getItemsUsed()
    {
        if ($this->_model_used && is_array($this->_model_used)) {
            if ($this->_model_dependencies) {
                return $this->_model_used + $this->getDependentOn($this->_model_used);
            }

            return $this->_model_used;
        } else {
            $names = array_keys($this->_model);
            return array_combine($names, $names);
        }
    }

    /**
     * Return an identifier the item specified by $forData
     *
     * basically transforms the fieldnames ointo oan IDn => value array
     *
     * @param mixed $forData Array value to filter on
     * @param array $href Or \ArrayObject
     * @return array That can by used as href
     */
    public function getKeyRef($forData, $href = array())
    {
        $keys = $this->getKeys();

        if (count($keys) == 1) {
            $key = reset($keys);
            if ($value = self::_getValueFrom($key, $forData)) {
                $href[\MUtil\Model::REQUEST_ID] = $value;
            }
        } else {
            $i = 1;
            foreach ($keys as $key) {
                if ($value = self::_getValueFrom($key, $forData)) {
                    $href[\MUtil\Model::REQUEST_ID . $i] = $value;
                }
            }
        }

        return $href;
    }

    /**
     * Returns an array containing the currently defined keys for this
     * model.
     *
     * When no keys are defined, the keys are derived from the model.
     *
     * @param boolean $reset If true, derives the key from the model.
     * @return array
     */
    public function getKeys($reset = false)
    {
        if ((! $this->_keys) || $reset) {
            $this->setKeys($this->getItemsFor(['key' => true]));
        }
        return $this->_keys;
    }

    /**
     * @return array alternative id => field name
     */
    public function getMaps(): array
    {
        return $this->_maps;
    }

    /**
     * Get a model level variable named $key
     *
     * @param string $key
     * @param mixed $default Optional default
     * @return mixed
     */
    public function getMeta($key, $default = null)
    {
        if (isset($this->_model_meta[$key])) {
            return $this->_model_meta[$key];
        }
        return $default;
    }

    public function getMetaModelLoader(): MetaModelLoader
    {
        return $this->modelLoader;
    }

    /**
     * The internal name of the model, used for joining models and sub forms, etc...
     *
     * @return string
     */
    public function getName()
    {
        return $this->modelName;
    }

    /**
     * Checks for and executes any actions to perform on a value after
     * loading the value
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return mixed The value to use instead
     */
    public function getOnLoad($value, $new, $name, array $context = array(), $isPost = false)
    {
        $call = $this->get($name, self::LOAD_TRANSFORMER);
        if ($call) {
            if (is_callable($call)) {
                $value = call_user_func($call, $value, $new, $name, $context, $isPost);
            } else {
                $value = $call;
            }
        }

        return $value;
    }

    /**
     * Checks for and executes any actions to perform on a value before
     * saving the value
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return mixed The value to save
     */
    public function getOnSave($value, $new, $name, array $context = array())
    {
        if ($call = $this->get($name, self::SAVE_TRANSFORMER)) {

            if (is_callable($call)) {
                $value = call_user_func($call, $value, $new, $name, $context);
            } else {
                $value = $call;
            }
        }

        return $value;
    }

    /**
     * Find out the order of the requested $name in the model
     *
     * @param string $name
     * @return int|null The order value of the requeste item or null if not defined
     */
    public function getOrder($name) {
        if (isset($this->_model_order[$name])) {
            return $this->_model_order[$name];
        }
    }

    /**
     * Get the model transformers
     *
     * @return array of ModelTransformerInterface
     */
    public function getTransformers()
    {
        return $this->_transformers;
    }

    /**
     * Get one result with a default if it does not exist
     *
     * @param string $name
     * @param string $name Field name
     * @param string $subkey Field key
     * @param mixed $default default value, if value does not exist
     * @return mixed
     */
    public function getWithDefault($name, $subkey, $default)
    {
        if ($this->has($name, $subkey)) {
            return $this->get($name, $subkey);
        }

        return $default;
    }

    /**
     * Returns True when the name exists in the model. When used
     * with a subkey, that subkey has to exist for the name.
     *
     * @param string $name Field name
     * @param string $subkey Optional field key
     * @return boolean
     */
    public function has(string $name, ?string $subkey = null): bool
    {
        if (null === $subkey) {
            return array_key_exists($name, $this->_model);
        } else {
            return isset($this->_model[$name][$subkey]);
        }
    }

    /**
     * Returns True when one of the names exists in the model.
     *
     * @param array $names of field names
     * @return boolean
     */
    public function hasAnyOf(array $names)
    {
        $check = array_combine($names, $names);

        foreach ($names as $name) {
            if (isset($this->_model[$name])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does the model have a dependencies?
     *
     * @return boolean
     */
    public function hasDependencies()
    {
        return (boolean) $this->_model_dependencies;
    }

    /**
     * Does this name or any of these names have a dependency at all or on the specific setting?
     *
     * @param mixed $name Field name or array of fields
     * @param string $setting Setting name
     * @return boolean
     */
    public function hasDependency($name, $setting = null)
    {
        $names = (array) $name;

        foreach ($this->_model_dependencies as $dependency) {
            if ($dependency instanceof DependencyInterface) {
                foreach ($names as $name) {
                    $settings = $dependency->getEffected($name);

                    if ($settings) {
                        if ((null === $setting) or isset($settings[$setting])) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Does this model track items in use?
     *
     * @return boolean
     */
    public function hasItemsUsed()
    {
        return (boolean) $this->_model_used;
    }

    /**
     * Does a certain Meta setting exist?
     *
     * @param string $key
     * @return boolean
     */
    public function hasMeta($key)
    {
        return isset($this->_model_meta[$key]);
    }

    /**
     * Does the item have a save transformer?
     *
     * @param string $name Item name
     * @return boolean
     */
    public function hasOnSave($name)
    {
        return $this->has($name, self::SAVE_TRANSFORMER);
    }

    /**
     * Does the item have a save when test?
     *
     * @param string $name Item name
     * @return boolean
     */
    public function hasSaveWhen($name)
    {
        return $this->has($name, self::SAVE_WHEN_TEST);
    }

    public function is($name, $key, $value)
    {
        return $value == $this->_getKeyValue($name, $key);
    }

    /**
     * Is the value of the field $name calculated automatically (returns true) or
     * only available when supplied in the data to be saved (returns false).
     *
     * @param string $name  The name of a field
     * @return boolean
     */
    public function isAutoSave($name)
    {
        return $this->_getKeyValue($name, self::AUTO_SAVE);
    }

    public function isMeta($key, $value)
    {
        return $this->getMeta($key) == $value;
    }

    /**
     * Must the model save field $name with this $value and / or this $new values.
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean True if the data can be saved
     */
    public function isSaveable($value, $new, $name, array $context = array())
    {
        if ($test = $this->get($name, self::SAVE_WHEN_TEST)) {

            if (is_callable($test)) {
                return call_user_func($test, $value, $new, $name, $context);
            }

            return $test;
        }

        return true;
    }

    public function isString($name)
    {
        if ($type = $this->get($name, 'type')) {
            return MetaModelInterface::TYPE_STRING == $type;
        }

        return true;
    }

    /**
     * Helper function that procesess the raw data after a load.
     *
     * @param mixed $data Nested array or \Traversable containing rows or iterator
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array or \Traversable Nested
     */
    public function processAfterLoad($data, $new = false, $isPostData = false): mixed
    {
        if (($this->_transformers || $isPostData) &&
            ($data instanceof \Traversable)) {
            $data = iterator_to_array($data, true);
        }

        foreach ($this->_transformers as $transformer) {
            $data = $transformer->transformLoad($this, $data, $new, $isPostData);
        }

        if ($this->getMeta(self::LOAD_TRANSFORMER) || $this->hasDependencies()) {
            if ($data instanceof \Traversable) {
                return new ItemCallbackIterator($data, array($this, '_processRowAfterLoad'));
            } else {
                // Create empty array, will be filled after first row to speed up performance
                $transformColumns = array();

                foreach ($data as $key => $row) {
                    $data[$key] = $this->processRowAfterLoad($row, $new, $isPostData, $transformColumns);
                }
            }
        }

        return $data;
    }


    /**
     * Helper function that procesess the raw data after a save.
     *
     * @param array $row Row array containing saved (and maybe not saved data)
     * @return array Nested
     */
    public function processAfterSave(array $row)
    {
        foreach ($this->_transformers as $transformer) {
            if ($transformer->triggerOnSaves()) {
                $row = $transformer->transformRowAfterSave($this, $this->processRowBeforeSave($row));
            } else {
                $row = $transformer->transformRowAfterSave($this, $row);
            }
        }

        return $row;
    }

    /**
     * Helper function that procesess the raw data before a save.
     *
     * @param array $row Row array containing saved (and maybe not saved data)
     * @return array Nested
     */
    public function processBeforeSave(array $row)
    {
        foreach ($this->_transformers as $transformer) {
            $row = $transformer->transformRowBeforeSave($this, $row);
        }

        return $row;
    }

    /**
     * Process the changes in the model caused by dependencies, using this data.
     *
     * @param array $data The input data
     * @param boolean $new True when it is a new item not saved in the model
     * @return array The possibly change input data
     */
    public function processDependencies(array $data, $new)
    {
        foreach ($this->_model_dependencies as $dependency) {
            if ($dependency instanceof DependencyInterface) {

                $dependsOn = $dependency->getDependsOn();
                $context   = array_intersect_key($data, $dependsOn);

                // If there are required fields and all required fields are there
                if ($dependsOn && (count($context) === count($dependsOn))) {

                    $changes = $dependency->getChanges($context, $new);

                    if ($changes) {
                        // Here we could allow only those changes this dependency claims to change
                        // but as not specifying this correctly may lead to errors elsewhere
                        // I think there is enough reason for discipline in this not to perform
                        // this extra check. (Though I may change my mind in the future
                        $this->applyDependencyChanges($this, $changes, $data);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Helper function that procesess a single row of raw data after a load.
     *
     * @param array $row array containing row
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Row
     */
    public function processOneRowAfterLoad(array $row, $new = false, $isPostData = false)
    {
        $output = $this->processAfterLoad(array($row), $new, $isPostData);

        return reset($output);
    }

    /**
     * Process on load functions and dependencies
     *
     * @see addDependency()
     * @see setOnLoad()
     *
     * @param array $row The row values to load
     * @param boolean $new True when it is a new item not saved in the model
     * @param boolean $isPost True when passing on post data
     * @param array $transformColumns ignore:: cache to prevent repeated call's top getCol
     * @return array The possibly adapted array of values
     */
    public function processRowAfterLoad(array $row, $new = false, $isPost = false, &$transformColumns = array())
    {
        $newRow = $row;

        if (empty($transformColumns)) {
            if ($this->getMeta(self::LOAD_TRANSFORMER)) {
                $transformColumns = $this->getCol(self::LOAD_TRANSFORMER);
            }
        }

        foreach ($transformColumns as $name => $call) {
            $value = isset($newRow[$name]) ? $newRow[$name] : null;

            // Don't use getOnLoad to prevent additional overhead since we have the
            // callable already
            if (is_callable($call)) {
                $newRow[$name] = call_user_func($call, $value, $new, $name, $row, $isPost);
            } else {
                $newRow[$name] = $call;
            }
        }

        if ($this->_model_dependencies && $this->_model_enable_dependencies) {
            $newRow = $this->processDependencies($newRow, $new);
        }

        return $newRow + $this->getCol('value');
    }

    /**
     * @inheritdoc 
     */
    public function processRowBeforeSave(array $row, bool $new = false): array
    {
        $output = [];
        foreach ($row as $name => $value) {
            if ('' === $value) {
                // Remove default empty string values.
                $value = null;
            }

            if ($this->isSaveable($value, $new, $name, $row)) {
                $output[$name] = $this->getOnSave($value, $new, $name, $row);
            }
        }
        
        return $output;        
    }
    
    /**
     * Remove one attribute for a field name in the model.
     *
     * Example:
     * <code>
     * $this->remove('field_x', 'label') ;
     * </code>
     * This will remove the label attribute from the field_x
     *
     * @param string $name The fieldname
     * @param string $key The name of the key
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function remove($name, $key = null)
    {
        if (null === $key) {
            if (isset($this->_model[$name])) {
                unset($this->_model[$name]);
                unset($this->_model_order[$name]);
            }
        } elseif (isset($this->_model[$name][$key])) {
            unset($this->_model[$name][$key]);
        }

        return $this;
    }

    /**
     * Reset the processing / display order for getItemsOrdered().
     *
     * Model items are displayed in the order they are first set() by the code.
     * Using this functions resets this list and allows you to start over
     * and determine the display order by the order you set() the items from
     * now on.
     *
     * @see getItemsOrdered()
     *
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function resetOrder()
    {
        $this->_model_order = null;
        return $this;
    }

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null): array
    {
        $beforeValues = $this->processBeforeSave($newValues);

        $resultValues = $this->_save($beforeValues, $filter);

        $afterValues  = $this->processAfterSave($resultValues);

        if ($this->getMeta(self::LOAD_TRANSFORMER) || $this->hasDependencies()) {
            return $this->processRowAfterLoad($afterValues, false);
        } else {
            return $afterValues;
        }
    }

    /**
     * Set one or more attributes for a field names in the model.
     *
     * Example:
     * <code>
     * $this->set('field_x', 'save', true) ;
     * $this->set('field_x', array('save' => true)) ;
     * </code>
     * Both set the attribute 'save' to true for 'field_x'.
     *
     * @param string $name        The fieldname
     * @param mixed  $arrayOrKey1 A key => value array or the name of the first key, see Ra::Args::pairs()
     * @param mixed  $value1      The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2        Optional second key when $arrayOrKey1 is a string
     * @param mixed  $value2      Optional second value when $arrayOrKey1 is a string,
     *                            an unlimited number of $key values pairs can be given.
     * @return \Zalt\Model\MetaModelInterface
     */
    public function set($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $args = func_get_args();
        $args = Ra::pairs($args, 1);

        if ($args) {
            foreach ($args as $key => $value) {
                // If $key end with ] it is array value
                if (substr($key, -1) == ']') {
                    if (substr($key, -2) == '[]') {
                        // If $key ends with [], append it to array
                        $key    = substr($key, 0, -2);
                        $this->_model[$name][$key][] = $value;
                    } else {
                        // Otherwise extract subkey
                        $pos    = strpos($key, '[');
                        $subkey = substr($key, $pos + 1, -1);
                        $key    = substr($key, 0, $pos);

                        $this->_model[$name][$key][$subkey] = $value;
                    }
                } else {
                    $this->_model[$name][$key] = $value;
                    foreach ($this->linkedDefaults as $defaultkey => $defaultValues) {
                        if (($defaultkey == $key) && (isset($defaultValues[$value]) && is_array($defaultValues[$value]))) {
                            foreach ($defaultValues[$value] as $dKey => $dVal) {
                                if (! array_key_exists($dKey, $this->_model[$name])) {
                                    $this->_model[$name][$dKey] = $dVal;
                                }
                            }
                        }
                    }
                }
            }
        } elseif (!array_key_exists($name, $this->_model)) {
            // Make sure this key occurs
            $this->_model[$name] = array();
        }

        // Now set the order (repeat always, because order can be changed later on)
        if (isset($this->_model[$name]['order'])) {
            $order = $this->_model[$name]['order'];
        } elseif (isset($this->_model_order[$name]) && is_int($this->_model_order[$name])) {
            $order = $this->_model_order[$name];
        } else {
            $order = 0;
            if (is_array($this->_model_order)) {
                $order = max(array_values($this->_model_order));
            }
            $order += $this->orderIncrement;
        }
        $this->_model_order[$name] = $order;

        return $this;
    }

    /**
     * Set the value to be an alias of another field
     *
     * @param string $name
     * @param string $aliasOf
     * @return \Zalt\Model\MetaModelInterface
     * @throws \Zalt\Model\Exception\MetaModelException
     */
    public function setAlias($name, $aliasOf)
    {
        if ($this->has($aliasOf)) {
            $this->set($name, self::ALIAS_OF, $aliasOf);
            return $this;
        }
        throw new MetaModelException("Alias for '$name' set to non existing field '$aliasOf'");
    }

    /**
     * Is the value of the field $name calculated automatically (set to true) or
     * only available when supplied in the data to be saved (set to false).
     *
     * @param string $name  The name of a field
     * @param boolean $value
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setAutoSave($name, $value = true)
    {
        $this->set($name, self::AUTO_SAVE, $value);
        return $this;
    }

    /**
     * Update the number of rows changed.
     *
     * @param int $changed
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    protected function setChanged($changed = 0)
    {
        $this->_changedCount = $changed;

        return $this;
    }

    /**
     * Set attributes for all or some fields in the model.
     *
     * Example 1, all fields:
     * <code>
     * $this->setCol('save', true) ;
     * $this->setCol(array('save' => true)) ;
     * </code>
     * both set the attribute 'save' to true for all fields.
     *
     * Example 2, some fields:
     * <code>
     * $this->setCol(array('x', 'y', 'z'), 'save', true) ;
     * $this->setCol(array('x', 'y', 'z'), array('save' => true)) ;
     * </code>
     * both set the attribute 'save' to true for the x, y and z fields.
     *
     * @param $namesOrKeyArray When array and there is more than one parameter an array of field names
     * @param string|array $arrayOrKey1 A key => value array or the name of the first key
     * @param mixed $value1 The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2 Optional second key when $arrayOrKey1 is a string
     * @param mixed $value2 Optional second value when $arrayOrKey1 is a string, an unlimited number of $key values pairs can be given.
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setCol($namesOrKeyArray, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        if (is_array($namesOrKeyArray) && $arrayOrKey1) {
            $names = $namesOrKeyArray;
            $skip = 1;
        } else {
            $names = array_keys($this->_model);
            $skip = 0;
        }
        $args = func_get_args();
        $args = Ra::pairs($args, $skip);

        foreach ($names as $name) {
            $this->set($name, $args);
        }

        return $this;
    }

    /**
     * Set attributes for all or some fields in the model, but only when those eattributes do not exist (or are null)
     *
     * Example 1, all fields:
     * <code>
     * $this->setDefaults('save', true) ;
     * $this->setDefaults(array('save' => true)) ;
     * </code>
     * both set the attribute 'save' to true for all fields where it is not null.
     *
     * Example 2, some fields:
     * <code>
     * $this->setDefaults(array('x', 'y', 'z'), 'save', true) ;
     * $this->setDefaults(array('x', 'y', 'z'), array('save' => true)) ;
     * </code>
     * both set the attribute 'save' to true for the x, y and z fields, unless it was already set to false.
     *
     * @param $namesOrKeyArray When array and there is more than one parameter an array of field names
     * @param string|array $arrayOrKey1 A key => value array or the name of the first key
     * @param mixed $value1 The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2 Optional second key when $arrayOrKey1 is a string
     * @param mixed $value2 Optional second value when $arrayOrKey1 is a string, an unlimited number of $key values pairs can be given.
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setDefault($namesOrKeyArray, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        if (is_array($namesOrKeyArray) && $arrayOrKey1) {
            $names = $namesOrKeyArray;
            $skip = 1;
        } else {
            $names = array_keys($this->_model);
            $skip = 0;
        }
        $args = func_get_args();
        $args = Ra::pairs($args, $skip);

        foreach ($names as $name) {
            foreach ($args as $key => $value) {
                if (! $this->has($name, $key)) {
                    $this->set($name, $key, $value);
                }
            }
        }

        return $this;
    }

    /**
     * Similar to set, but sets only when the $mame already exists in the model.
     *
     * This is usefull when not every instance of the model will have these fields, but
     * they might exist in many instances.
     *
     * Example:
     * <code>
     * $this->setIfExists('field_x', 'save', true) ;
     * $this->setIfExists('field_x', array('save' => true)) ;
     * </code>
     * Both set the attribute 'save' to true for 'field_x'.
     *
     * @param string $name        The fieldname
     * @param mixed  $arrayOrKey1 A key => value array or the name of the first key, see \MUtil_Args::pairs()
     * @param mixed  $value1      The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2        Optional second key when $arrayOrKey1 is a string
     * @param mixed  $value2      Optional second value when $arrayOrKey1 is a string,
     *                            an unlimited number of $key values pairs can be given.
     * @return boolean True when the $name exists in this model.
     */
    public function setIfExists($name, $arrayOrKey1 = array(), $value1 = null, $key2 = null, $value2 = null)
    {
        if ($this->has($name)) {
            $args = func_get_args();
            $args = Ra::pairs($args, 1);

            $this->set($name, $args);

            return true;
        }

        return false;
    }

    /**
     * Sets the keys, processing the array key.
     *
     * When an array key is numeric \MUtil\Model::REQUEST_ID is used.
     * When there is more than one key a increasing number is added to
     * \MUtil\Model::REQUEST_ID starting with 1.
     *
     * String key names are left as is.
     *
     * @param array $keys [alternative_]name or number => name
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setKeys(array $keys)
    {
        $this->_keys = [];

        if (count($keys) == 1) {
            $name = reset($keys);
            if (is_numeric(key($keys))) {
                $this->_keys[\MUtil\Model::REQUEST_ID] = $name;
            } else {
                $this->_keys[key($keys)] = $name;
            }
        } else {
            $i = 1;
            foreach ($keys as $idx => $name) {
                if (is_numeric($idx)) {
                    $this->_keys[\MUtil\Model::REQUEST_ID . $i] = $name;
                    $i++;
                } else {
                    $this->_keys[$idx] = $name;
                }
            }
        }
        foreach ($this->_keys as $alias => $field) {
            $this->_maps[$alias] = $field;
        }

        return $this;
    }

    /**
     * @param array $map alternative id => field name
     * @return \Zalt\Model\MetaModelInterface  (continuation pattern)
     */
    public function setMaps(array $map): MetaModelInterface
    {
        $this->_maps = $map;
        return $this;
    }

    /**
     * Set a model level variable named $key to $value
     *
     * @param string $key
     * @param mixed $value
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setMeta($key, $value)
    {
        $this->_model_meta[$key] = $value;
        return $this;
    }

    /**
     * Set attributes for a specified array of field names in the model.
     *
     * Example:
     * <code>
     * $this->setMulti(array('field_x', 'field_y'), 'save', true) ;
     * $this->setMulti(array('field_x', 'field_y'), array('save' => true)) ;
     * </code>
     * both set the attribute 'save' to true for 'field_x' and 'field_y'.
     *
     * @param array $names An array of fieldnames
     * @param string|array $arrayOrKey1 A key => value array or the name of the first key
     * @param mixed $value1 The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2 Optional second key when $arrayOrKey1 is a string
     * @param mixed $value2 Optional second value when $arrayOrKey1 is a string, an unlimited number of $key values pairs can be given.
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setMulti(array $names, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
       $args = Ra::pairs(func_get_args(), 1);

        foreach ($names as $name) {
            $this->set($name, $args);
        }

        return $this;
    }

    /**
     * Sets a name to automatically change a value after a load.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type:
     *              callable($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
     * @return MetaModelInterface (continuation pattern)
     */
    public function setOnLoad($name, $callableOrConstant)
    {
        // Make sure we store that there is some OnLoad function.
        $this->setMeta(self::LOAD_TRANSFORMER, true);
        $this->set($name, self::LOAD_TRANSFORMER, $callableOrConstant);
        return $this;
    }

    /**
     * Sets a name to an automatically determined or changed of value before a save.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type:
     *          callable($value, $isNew = false, $name = null, array $context = array())
     * @return MetaModelInterface (continuation pattern)
     */
    public function setOnSave($name, $callableOrConstant)
    {
        $this->set($name, self::SAVE_TRANSFORMER, $callableOrConstant);
        return $this;
    }

    /**
     * Set this field to be saved whenever there is anything to save at all.
     *
     * @param string $name The fieldname
     * @return MetaModelInterface (continuation pattern)
     */
    public function setSaveOnChange($name)
    {
        $this->setAutoSave($name);
        return $this->setSaveWhen($name, true);
    }

    /**
     * Set this field to be saved whenever a constant is true or a callable returns true.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type: callable($value, $isNew = false, $name = null, array $context = array()) boolean
     * @return MetaModelInterface (continuation pattern)
     */
    public function setSaveWhen($name, $callableOrConstant)
    {
        $this->set($name, self::SAVE_WHEN_TEST, $callableOrConstant);
        return $this;
    }

    /**
     * Set this field to be saved only when it is a new item.
     *
     * @param string $name The fieldname
     * @return MetaModelInterface (continuation pattern)
     */
    public function setSaveWhenNew($name)
    {
        $this->setAutoSave($name);
        return $this->setSaveWhen($name, array(__CLASS__, 'whenNew'));
    }

    /**
     * Set this field to be saved only when it is not empty.
     *
     * @param string $name The fieldname
     * @return MetaModelInterface (continuation pattern)
     */
    public function setSaveWhenNotNull($name)
    {
        return $this->setSaveWhen($name, array(__CLASS__, 'whenNotNull'));
    }

    /**
     * set the model transformers
     *
     * @param array $transformers of ModelTransformerInterface
     * @return MetaModelInterface (continuation pattern)
     */
    public function setTransformers(array $transformers)
    {
        $this->_transformers = array();
        foreach ($transformers as $transformer) {
            $this->addTransformer($transformer);
        }
        return $this;
    }

    /**
     * Start track usage, i.e. each name used in a call to get()
     *
     * @param boolean $value
     */
    public function trackUsage($value = true)
    {
        if ($value) {
            // Restarts the tracking
            $this->_model_used = $this->getKeys();
        } else {
            $this->_model_used = false;
        }
    }

    /**
     * A ModelAbstract->setSaveWhen() function that true when a new item is saved..
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public static function whenNew($value, $isNew = false, $name = null, array $context = array())
    {
        return $isNew;
    }

    /**
     * A ModelAbstract->setSaveWhen() function that true when the value
     * is not null.
     *
     * @see setSaveWhen()
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return boolean
     */
    public static function whenNotNull($value, $isNew = false, $name = null, array $context = array())
    {
        return null !== $value;
    }
}