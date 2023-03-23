<?php

// declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

use Zalt\Model\Bridge\BridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Transform\ModelTransformerInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @since      Class available since version 1.0
 */
interface MetaModelInterface
{
    /**
     * FIlter constant for like statements
     */
    const FILTER_BETWEEN_MAX = 'max';

    /**
     * FIlter constant for like statements
     */
    const FILTER_BETWEEN_MIN = 'min';

    /**
     * FIlter constant for like statements
     */
    const FILTER_CONTAINS = 'like';

    /**
     * Type identifiers for calculated fields
     */
    const TYPE_NOVALUE      = 0;

    /**
     * Type identifiers for string fields, default type
     */
    const TYPE_STRING       = 1;

    /**
     * Type identifiers for numeric fields
     */
    const TYPE_NUMERIC      = 2;

    /**
     * Type identifiers for date fields
     */
    const TYPE_DATE         = 3;

    /**
     * Type identifiers for date time fields
     */
    const TYPE_DATETIME     = 4;

    /**
     * Type identifiers for time fields
     */
    const TYPE_TIME         = 5;

    /**
     * Type identifiers for sub models that can return multiple row per item
     */
    const TYPE_CHILD_MODEL  = 6;

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
    public function addDependency($dependency, $dependsOn = null, array $effects = null,  $key = null);

    /**
     * @param string $alias Alternative to map to
     * @param string $fieldName Existing field
     * @return \Zalt\Model\MetaModelInterface (continuation pattern) 
     */
    public function addMap(string $alias, string $fieldName): MetaModelInterface;
    
    /**
     * Add a 'submodel' field to the model.
     *
     * You get a nested join where a set of rows is placed in the $name field
     * of each row of the parent model.
     *
     * @param DataReaderInterface $model
     * @param array $joins The join fields for the sub model
     * @param string $name Optional 'field' name, otherwise model name is used
     * @return \MUtil\Model\Transform\NestedTransformer The added transformer
     */
    public function addModel(DataReaderInterface $model, array $joins, $name = null);

    /**
     * Add a model transformer
     *
     * @param \Zalt\Model\Transform\ModelTransformerInterface $transformer
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function addTransformer(ModelTransformerInterface $transformer);

    /**
     * Remove all non-used elements from a form by setting the elementClasses to None.
     *
     * Checks for dependencies and keys to be included
     *
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function clearElementClasses();

    /**
     * Creates a validator that checks that this value is used in no other
     * row in the table of the $name field, except that row itself.
     *
     * If $excludes is specified it is used to create db_fieldname => $_POST mappings.
     * When db_fieldname is numeric it is assumed both should be the same.
     *
     * If no $excludes the model creates a filter using the primary key of the table.
     *
     * @param string|array $name The name of a model field in the model or an array of them.
     * @return \MUtil\Validate\Model\UniqueValue A validator.
     * /
    public function createUniqueValidator($name);

    /**
     * Delete all, one or some values for a certain field name.
     *
     * @param string $name Field name
     * @param string|array|null $arrayOrKey1 Null or the name of a single attribute or an array of attribute names
     * @param string $key2 Optional a second attribute name.
     */
    public function del($name, $arrayOrKey1 = null, $key2 = null);

    /**
     * Disable the onload settings. This is sometimes needed for speed/
     *
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function disableOnLoad();

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
    public function get($name, $arrayOrKey1 = null, $key2 = null);

    /**
     * Returns the field that name is an Alias of
     *
     * @param string $name
     * @return string
     */
    public function getAlias($name);

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
     * @return array name => value
     */
    public function getCol($columnName);

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
    public function getColNames($columnName);

    /**
     * Get the dependencies this name has a dependency to at all or on the specific setting
     *
     * @param mixed $name Field name or array of fields
     * @param string $setting Setting name
     * @return array of Dependencies
     */
    public function getDependencies($name, $setting = null);

    /**
     * Get the names of the fields of the dependencies this name has a dependency
     * to at all or on the specific setting
     *
     * @param mixed $name Field name or array of fields
     * @param string $setting Setting name
     * @return array of name => name
     */
    public function getDependentOn($name, $setting = null);

    /**
     * Returns all the field names in this model
     *
     * @return array Of names
     */
    public function getItemNames();

    /**
     * Returns all the field names that have the properties passed in the parameters
     *
     * @param array ...$args A single key value array or a sequence of items made into an array using Ra::pairs() 
     * @return array Of names
     */
    public function getItemsFor(...$args);

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
    public function getItemsOrdered();

    /**
     * The names of the items called using get() since the last
     * call to trackUsage(true).
     *
     * @return array name => name
     */
    public function getItemsUsed();

    /**
     * Return an identifier the item specified by $forData
     *
     * basically transforms the fieldnames ointo oan IDn => value array
     *
     * @param mixed $forData Array value to vilter on
     * @param array $href Or \ArrayObject
     * @return array That can by used as href
     */
    public function getKeyRef($forData, $href = array());

    /**
     * Returns an array containing the currently defined keys for this
     * model.
     *
     * When no keys are defined, the keys are derived from the model.
     *
     * @param boolean $reset If true, derives the key from the model.
     * @return array
     */
    public function getKeys($reset = false);

    /**
     * @return array alternative id => field name
     */
    public function getMaps(): array;    
    
    /**
     * Get a model level variable named $key
     *
     * @param string $key
     * @param mixed $default Optional default
     * @return mixed
     */
    public function getMeta($key, $default = null);
    
    public function getMetaModelLoader(): MetaModelLoader;
    
    /**
     * The internal name of the model, used for joining models and sub forms, etc...
     *
     * @return string
     */
    public function getName();

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
    public function getOnLoad($value, $new, $name, array $context = array(), $isPost = false);

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
    public function getOnSave($value, $new, $name, array $context = array());

    /**
     * Find out the order of the requested $name in the model
     *
     * @param string $name
     * @return int|null The order value of the requeste item or null if not defined
     */
    public function getOrder($name);

    /**
     * Get the model transformers
     *
     * @return array of \Zalt\Model\Transform\ModelTransformerInterface
     */
    public function getTransformers();

    /**
     * Get one result with a default if it does not exist
     *
     * @param string $name
     * @param string $name Field name
     * @param string $subkey Field key
     * @param mixed $default default value, if value does not exist
     * @return mixed
     */
    public function getWithDefault($name, $subkey, $default);

    /**
     * Returns True when the name exists in the model. When used
     * with a subkey, that subkey has to exist for the name.
     *
     * @param string $name Field name
     * @param string $subkey Optional field key
     * @return boolean
     */
    public function has(string $name, string $subkey = null): bool;

    /**
     * Returns True when one of the names exists in the model.
     *
     * @param array $names of field names
     * @return boolean
     */
    public function hasAnyOf(array $names);

    /**
     * Does the model have a dependencies?
     *
     * @return boolean
     */
    public function hasDependencies();

    /**
     * Does this name or any of these names have a dependency at all or on the specific setting?
     *
     * @param mixed $name Field name or array of fields
     * @param string $setting Setting name
     * @return boolean
     */
    public function hasDependency($name, $setting = null);

    /**
     * Does this model track items in use?
     *
     * @return boolean
     */
    public function hasItemsUsed();

    /**
     * Does a certain Meta setting exist?
     *
     * @param string $key
     * @return boolean
     */
    public function hasMeta($key);

    /**
     * Does the item have a save transformer?
     *
     * @param string $name Item name
     * @return boolean
     */
    public function hasOnSave($name);

    /**
     * Does the item have a save when test?
     *
     * @param string $name Item name
     * @return boolean
     */
    public function hasSaveWhen($name);

    /**
     * @param string $name
     * @param mixed $key
     * @param mixed $value
     * @return boolean True when the key for that name has the same value
     */
    public function is($name, $key, $value);

    /**
     * Is the value of the field $name calculated automatically (returns true) or
     * only available when supplied in the data to be saved (returns false).
     *
     * @param string $name  The name of a field
     * @return boolean
     */
    public function isAutoSave($name);

    /**
     * @param $key
     * @param $value
     * @return boolean True when the meta key exists and has that value
     */
    public function isMeta($key, $value);

    /**
     * @param $name
     * @return boolean True when the field is a string type
     */
    public function isString($name);

    /**
     * Helper function that procesess the raw data after a load.
     *
     * @see \MUtil\Model\SelectModelPaginator
     *
     * @param mixed $data Nested array or \Traversable containing rows or iterator
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array or \Traversable Nested
     */
    public function processAfterLoad($data, $new = false, $isPostData = false): mixed;

    /**
     * Helper function that procesess the raw data after a save.
     *
     * @param array $row Row array containing saved (and maybe not saved data)
     * @return array Nested
     */
    public function processAfterSave(array $row);

    /**
     * Helper function that procesess the raw data before a save.
     *
     * @param array $row Row array containing saved (and maybe not saved data)
     * @return array Nested
     */
    public function processBeforeSave(array $row);

    /**
     * Process the changes in the model caused by dependencies, using this data.
     *
     * @param array $data The input data
     * @param boolean $new True when it is a new item not saved in the model
     * @return array The possibly change input data
     */
    public function processDependencies(array $data, $new);

    /**
     * Helper function that procesess a single row of raw data after a load.
     *
     * @param array $row array containing row
     * @param boolean $new True when it is a new item
     * @param boolean $isPostData With post data, unselected multiOptions values are not set so should be added
     * @return array Row
     */
    public function processOneRowAfterLoad(array $row, $new = false, $isPostData = false);

    /**
     * @param array $row
     * @param bool $isNew
     * @param array $fullRow Optional full dataset for save context
     * @return array
     */
    public function processRowBeforeSave(array $row, bool $new = false, array $fullRow = []): array;
    
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
    public function remove($name, $key = null);

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
    public function resetOrder();

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
     * @param mixed  $arrayOrKey1 A key => value array or the name of the first key, see \MUtil_Args::pairs()
     * @param mixed  $value1      The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2        Optional second key when $arrayOrKey1 is a string
     * @param mixed  $value2      Optional second value when $arrayOrKey1 is a string,
     *                            an unlimited number of $key values pairs can be given.
     * @return \Zalt\Model\MetaModelInterface
     */
    public function set($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null);

    /**
     * Set the value to be an alias of another field
     *
     * @param string $name
     * @param string $aliasOf
     * @return \Zalt\Model\MetaModelInterface
     * @throws \MUtil\Model\ModelException
     */
    public function setAlias($name, $aliasOf);

    /**
     * Is the value of the field $name calculated automatically (set to true) or
     * only available when supplied in the data to be saved (set to false).
     *
     * @param string $name  The name of a field
     * @param boolean $value
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setAutoSave($name, $value = true);

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
    public function setCol($namesOrKeyArray, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null);

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
    public function setDefault($namesOrKeyArray, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null);

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
    public function setIfExists($name, $arrayOrKey1 = array(), $value1 = null, $key2 = null, $value2 = null);

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
    public function setKeys(array $keys);

    /**
     * @param array $map alternative id => field name
     * @return \Zalt\Model\MetaModelInterface  (continuation pattern)
     */
    public function setMaps(array $map): MetaModelInterface; 

    /**
     * Set a model level variable named $key to $value
     *
     * @param string $key
     * @param mixed $value
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setMeta($key, $value);

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
    public function setMulti(array $names, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null);

    /**
     * Sets a name to automatically change a value after a load.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type:
     *              callable($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setOnLoad($name, $callableOrConstant);

    /**
     * Sets a name to an automatically determined or changed of value before a save.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type:
     *          callable($value, $isNew = false, $name = null, array $context = array())
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setOnSave($name, $callableOrConstant);

    /**
     * Set this field to be saved whenever there is anything to save at all.
     *
     * @param string $name The fieldname
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setSaveOnChange($name);

    /**
     * Set this field to be saved whenever a constant is true or a callable returns true.
     *
     * @param string $name The fieldname
     * @param mixed $callableOrConstant A constant or a function of this type: callable($value, $isNew = false, $name = null, array $context = array()) boolean
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setSaveWhen($name, $callableOrConstant);

    /**
     * Set this field to be saved only when it is a new item.
     *
     * @param string $name The fieldname
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setSaveWhenNew($name);

    /**
     * Set this field to be saved only when it is not empty.
     *
     * @param string $name The fieldname
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setSaveWhenNotNull($name);

    /**
     * set the model transformers
     *
     * @param array $transformers of \Zalt\Model\Transform\ModelTransformerInterface
     * @return \Zalt\Model\MetaModelInterface (continuation pattern)
     */
    public function setTransformers(array $transformers);

    /**
     * Start track usage, i.e. each name used in a call to get()
     *
     * @param boolean $value
     */
    public function trackUsage($value = true);
}