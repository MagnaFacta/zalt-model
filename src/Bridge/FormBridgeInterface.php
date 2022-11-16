<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
interface FormBridgeInterface extends BridgeInterface
{
    public function add($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null);

    public function addFilter($name, $filter, $options = array());

    /**
     *
     * @param string $elementName
     * @param mixed $validator
     * @param boolean $breakChainOnFailure
     * @param mixed $options
     * @return mixed
     */
    public function addValidator($elementName, $validator, $breakChainOnFailure = false, $options = array());

    /**
     * Returns the allowed options for a certain key or all options if no
     * key specified
     *
     * @param string $key
     * @return array
     */
    public function getAllowedOptions($key = null);

    /**
     *
     * @return mixed
     */
    public function getForm();

    /**
     * Retrieve a tab from a TabForm to add extra content to it
     *
     * @param string $name
     * @return mixed
     */
    public function getTab($name);

    /**
     * Set the allowed options for a certain key to the specified options array
     *
     * @param string $key
     * @param array $options
     * @return \Zalt\Model\Bridge\FormBridgeInterface
     */
    public function setAllowedOptions($key, $options);

    /**
     * @param mixed $form
     * @return void
     */
    public function setForm(mixed $form): void;
        
}