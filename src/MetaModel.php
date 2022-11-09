<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model;

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
     * Identifier for alias fields
     */
    const ALIAS_OF  = 'alias_of';

    /**
     * Contains the per field settings of the model
     *
     * @var array fieldname => array(settings)
     */
    private array $_model = [];

    /**
     * The order in which field names where ->set() since
     * the last ->resetOrder() - minus those not set.
     *
     * @var array fieldname => int
     */
    private array $_model_order = [];

    /**
     * Contains the (order in which) fields where accessed using
     * ->get(), containing only those fields that where accesed.
     *
     * @var array fieldname => fieldname
     */
    private array $_model_used = [];

    /**
     * The increment for item ordering, default is 10
     *
     * @var int
     */
    public int $orderIncrement = 10;

    /**
     * @param string $name
     * @param string $key
     * @return mixed field value
     */
    protected function _getKeyValue(string $name, string $key): mixed
    {
        if (isset($this->_model[$name][$key])) {
            $value = $this->_model[$name][$key];

//            if ($value instanceof \MUtil_Lazy_LazyInterface) {
//                $value = \MUtil_Lazy::rise($value);
//            }

            return $value;
        }
        if ($name = $this->getAlias($name)) {
            return $this->_getKeyValue($name, $key);
        }

        return null;
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
    public function get($name, $arrayOrKey1 = null, $key2 = null): mixed
    {
        $args = func_get_args();
        $args = \MUtil_Ra::args($args, 1);

        $this->_model_used[$name] = $name;

        switch (count($args)) {
            case 0:
                if (isset($this->_model[$name])) {
//                    if ($this->_model[$name] instanceof \MUtil_Lazy_LazyInterface) {
//                        $result = \MUtil_Lazy::rise($this->_model[$name]);
//                    } else {
                        $result = $this->_model[$name];
//                    }
                    if ($alias = $this->getAlias($name)) {
                        $result = $result + $this->get($alias);
                    }
                    return $result;
                } else {
                    return [];
                }

            case 1:
                return $this->_getKeyValue($name, reset($args));

            default:
                $results = [];
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
     * @return ?string
     */
    public function getAlias($name): ?string
    {
        if (isset($this->_model[$name][self::ALIAS_OF])) {
            return $this->_model[$name][self::ALIAS_OF];
        }
        return null;
    }

    /**
     * Returns True when the name exists in the model. When used
     * with a subkey, that subkey has to exist for the name.
     *
     * @param string $name Field name
     * @param ?string $subkey Optional field key
     * @return bool
     */
    public function has(string $name, string $subkey = null): bool
    {
        if (null === $subkey) {
            return array_key_exists($name, $this->_model);
        } else {
            return (bool) isset($this->_model[$name][$subkey]);
        }
    }

    /**
     * Does this model track items in use?
     *
     * @return bool
     */
    public function hasItemsUsed(): bool
    {
        return (bool) $this->_model_used;
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
     * @param mixed  $arrayOrKey1 A key => value array or the name of the first key, see \MUtil_Args::pairs()
     * @param mixed  $value1      The value for $arrayOrKey1 or null when $arrayOrKey1 is an array
     * @param string $key2        Optional second key when $arrayOrKey1 is a string
     * @param mixed  $value2      Optional second value when $arrayOrKey1 is a string,
     *                            an unlimited number of $key values pairs can be given.
     * @return MetaModelInterface (continuation pattern)
     */
    public function set($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null): MetaModelInterface
    {
        $args = func_get_args();
        $args = Ra::pairs($args, 1);

        if ($args) {
            foreach ($args as $key => $value) {
                // If $key end with ] it is array value
                if (str_ends_with($key, ']')) {
                    if (str_ends_with($key, '[]')) {
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
                    if ('type' == $key) {
                        // $defaults = Model::getTypeDefaults($value);
                        $defaults = [];
                        if ($defaults) {
                            foreach ($defaults as $dKey => $value) {
                                if (! array_key_exists($dKey, $this->_model[$name])) {
                                    $this->_model[$name][$dKey] = $value;
                                }
                            }
                        }
                    }
                }
            }
        } elseif (!array_key_exists($name, $this->_model)) {
            // Make sure this key occurs
            $this->_model[$name] = [];
        }

        // Now set the order (repeat always, because order can be changed later on)
        if (isset($this->_model[$name]['order'])) {
            $order = $this->_model[$name]['order'];
        } elseif (isset($this->_model_order[$name]) && is_int($this->_model_order[$name])) {
            $order = $this->_model_order[$name];
        } else {
            $order = 0;
            if ($this->_model_order) {
                $order = max(array_values($this->_model_order));
            }
            $order += $this->orderIncrement;
        }
        $this->_model_order[$name] = $order;

        return $this;
    }
}