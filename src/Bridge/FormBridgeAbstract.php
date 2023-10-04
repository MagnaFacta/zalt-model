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
use Zalt\Late\RepeatableInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\MetaModelException;
use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
abstract class FormBridgeAbstract implements FormBridgeInterface
{
    const AUTO_OPTIONS       = 'auto';
    const CHECK_OPTIONS      = 'check';
    const DATE_OPTIONS       = 'date';
    const DISPLAY_OPTIONS    = 'display';
    const EXHIBIT_OPTIONS    = 'exhibit';
    const FAKESUBMIT_OPTIONS = 'fakesubmit';
    const FILE_OPTIONS       = 'file';
    const GROUP_OPTIONS      = 'displaygroup';
    const JQUERY_OPTIONS     = 'jquery';
    const MULTI_OPTIONS      = 'multi';
    const PASSWORD_OPTIONS   = 'password';
    const SUBFORM_OPTIONS    = 'subform';
    const TAB_OPTIONS        = 'tab';
    const TEXT_OPTIONS       = 'text';
    const TEXTAREA_OPTIONS   = 'textarea';

    // First list html attributes, then Zend attributes, lastly own attributes
    private $_allowedOptions = [
        self::AUTO_OPTIONS       => ['elementClass', 'multiOptions'],
        self::CHECK_OPTIONS      => ['checkedValue', 'uncheckedValue'],
        self::DATE_OPTIONS       => ['dateFormat', 'datePickerSettings', 'storageFormat'],
        self::DISPLAY_OPTIONS    => ['accesskey', 'addDecorators', 'autoInsertNoTagsValidator', 'autoInsertNotEmptyValidator', 'autosubmit', 'class', 'decorators', 'disabled', 'disableTranslator', 'description', 'escape', 'escapeDescription', 'label', 'labelplacement', 'onclick', 'placeholder', 'readonly', 'required', 'tabindex', 'value', 'showLabels'],
        self::EXHIBIT_OPTIONS    => ['formatFunction', 'itemDisplay', 'nohidden'],
        self::FAKESUBMIT_OPTIONS => ['label', 'tabindex', 'disabled'],
        self::FILE_OPTIONS       => ['accept', 'count', 'destination', 'extension', 'filename', 'valueDisabled'],
        self::GROUP_OPTIONS      => ['elements', 'legend', 'separator'],
        self::JQUERY_OPTIONS     => ['jQueryParams'],
        self::MULTI_OPTIONS      => ['disable', 'multiOptions', 'onchange', 'separator', 'size'],
        self::PASSWORD_OPTIONS   => ['renderPassword', 'repeatLabel'],
        self::SUBFORM_OPTIONS    => ['class', 'decorators', 'escape', 'form', 'label', 'tabindex'],
        self::TAB_OPTIONS        => ['value'],
        self::TEXT_OPTIONS       => ['maxlength', 'minlength', 'onblur', 'onchange', 'onfocus', 'onselect', 'size'],
        self::TEXTAREA_OPTIONS   => ['cols', 'decorators', 'rows', 'wrap'],
    ];
    
    protected string $dateTimeClass = "DateTimeInput";

    /**
     * When no size is set for a text-element, the size will be set to the minimum of the
     * maxsize and this value.
     *
     * @var int
     */
    public $defaultSize = 40;

    public FilterBridgeInterface $filterBridge;

    public MetaModelInterface $metaModel;

    public ValidatorBridgeInterface $validatorBridge;
    
    /**
     * @inheritDoc
     */
    public function __construct(protected DataReaderInterface $dataModel)
    {   
        if (! $this->dataModel instanceof FullDataInterface) {
            throw new MetaModelException("Only FullDataInterface objects are allowed as input for a FormBridge");
        }
        $this->metaModel = $this->dataModel->getMetaModel();
        $this->filterBridge = $this->getFilterBridge();
        $this->validatorBridge = $this->getValidatorBridge();
    }

    /**
     * Add the element to the form and apply any filters & validators
     *
     * @param string $name
     * @param mixed $element Element or element class name
     * @param array $options Element creation options
     * @param boolean $addFilters When true filters are added
     * @param boolean $addValidators When true validators are added
     * @return mixed
     */
    abstract protected function _addToForm($name, $element, $options = null, $addFilters = true, $addValidators = true): mixed;

    protected function _getStringLength(array &$options)
    {
        if (isset($options['minlength'])) {
            $stringlength['min'] = $options['minlength'];
            unset($options['minlength']);
        }
        if (isset($options['size']) && (! isset($options['maxlength']))) {
            $options['maxlength'] = $options['size'];
        }
        if (isset($options['maxlength'])) {
            if (! isset($options['size'])) {
                $options['size'] = min($options['maxlength'], $this->defaultSize);
            }
            $stringlength['max'] = $options['maxlength'];
        }

        return isset($stringlength) ? $stringlength : null;
    }

    /**
     * Returns the options from the allowedoptions array, using the supplied options first and trying
     * to find the missing ones in the model.
     *
     * @param string $name
     * @param array $options
     * @param array $allowedOptionsKeys containing arrays and string keys of $this->_allowedOptions
     * @return array
     */
    protected function _mergeOptions($name, array $options, ...$allowedOptionsKeys)
    {
        $allowedOptions = [];
        foreach ($allowedOptionsKeys as $allowedOptionsKey) {
            if (is_array($allowedOptionsKey)) {
                $allowedOptions = array_merge($allowedOptions, $allowedOptionsKey);
            } else {
                if (array_key_exists($allowedOptionsKey, $this->_allowedOptions)) {
                    $allowedOptions = array_merge($allowedOptions, $this->_allowedOptions[$allowedOptionsKey]);
                } else {
                     $allowedOptions[] = $allowedOptionsKey;
                }
            }
        }

        if ($allowedOptions) {
            // Remove options already filled. Using simple array addition
            // might trigger a lot of lazy calculations that are not needed.
            $allowedOptionsFlipped = array_flip($allowedOptions);

            $options = array_intersect_key($options, $allowedOptionsFlipped);

            // Now get allowed options from the model
            $modelOptions = $this->metaModel->get($name, $allowedOptions);

            // Merge them: first use supplied $options, and add missing values from model
            $options = (array) $options + (array) $modelOptions;
        }

        if (isset($options['autosubmit'])) {
            $autosubmit = $this->_moveOption('autosubmit', $options);

            $autosubmitClass = 'autosubmit';
            if (is_string($autosubmit)) {
                $autosubmitClass .= ' ' . $autosubmit;
            }

            if (isset($options['class'])) {
                $options['class'] = ' ' . $autosubmitClass;
            } else {
                $options['class'] = $autosubmitClass;
            }
        }

        return $options;
    }

    /**
     * Find $name in the $options array and unset it. If not found, return the $default value
     *
     * @param string $name
     * @param array $options
     * @param mixed $default
     * @return mixed
     */
    protected function _moveOption($name, array &$options, $default = null)
    {
        if (isset($options[$name])) {
            $result = $options[$name];
            unset($options[$name]);
            return $result;
        }

        return $default;
    }

    public function add($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = Ra::pairs(func_get_args(), 1);

        /**
         * As this method redirects to the correct 'add' method, we preserve the original options
         * while trying to find the needed ones in the model
         */
        $options = $options + $this->_mergeOptions($name, $options, self::AUTO_OPTIONS);

        if (isset($options['elementClass'])) {
            $method = 'add' . $options['elementClass'];
            unset($options['elementClass']);

        } else {
            if (isset($options['multiOptions'])) {
                $method = 'addSelect';
            } else {
                $method = 'addText';
            }
        }

        return $this->$method($name, $options);
    }

    public function addColorPicker($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS);


        return $this->_addToForm($name, 'ColorPicker' , $options);
    }

    public function addCheckbox($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        // Is often set for browse table, but should not be used here,
        // while the default ->add function does add it.
        $this->_moveOption('multiOptions', $options);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS, self::CHECK_OPTIONS);

        return $this->_addToForm($name, 'Checkbox', $options);
    }

    /**
     * Add a ZendX date picker to the form
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 \MUtil\Ra::pairs() name => value array
     * @return mixed
     */
    public function addDate($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,self::DATE_OPTIONS, self::DISPLAY_OPTIONS, self::JQUERY_OPTIONS, self::TEXT_OPTIONS);

        if (isset($options['dateFormat'])) {
            // Make sure the model knows the dateFormat (can be important for storage).
            $this->metaModel->set($name, 'dateFormat', $options['dateFormat']);
        }

        return $this->_addToForm($name, $this->dateTimeClass, $options);
    }

    /**
     * Add an element that just displays the value to the user
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 \MUtil\Ra::pairs() name => value array
     * @return mixed
     */
    public function addExhibitor($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = $this->_mergeOptions(
            $name,
            Ra::pairs(func_get_args(), 1),
            self::DATE_OPTIONS,
            self::DISPLAY_OPTIONS,
            self::EXHIBIT_OPTIONS,
            self::MULTI_OPTIONS
        );

        return $this->_addToForm($name, 'exhibitor', $options, false, false);
    }

    /**
     * Add an element that just displays the value to the user
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 Ra::pairs() name => value array
     * @return mixed
     */
    public function addFakeSubmit($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = $this->_mergeOptions(
            $name,
            Ra::pairs(func_get_args(), 1),
            self::FAKESUBMIT_OPTIONS
        );

        return $this->_addToForm($name, 'fakeSubmit', $options, true, false);
    }

    public function addHidden($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);


        return $this->_addToForm($name, 'Hidden', $options, true, false);
    }

    public function addHiddenMulti($name_args)
    {
        $args = Ra::args(func_get_args());

        foreach ($args as $name) {
            $this->addHidden($name);
        }
    }

    public function addHtml($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS);

        return $this->_addToForm($name, 'html', $options, false, false);
    }

    public function addList($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        if (! array_key_exists('size', $options)) {
            $count = count($options['multiOptions']);
            $options['size'] = $count > 5 ? 5 : $count + 1;
        }

        return $this->_addToForm($name, 'Select', $options);
    }

    /**
     * Adds a group of checkboxes (multicheckbox)
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 \MUtil\Ra::pairs() name => value array
     * @return mixed
     */
    public function addMultiCheckbox($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        return $this->_addToForm($name, 'MultiCheckbox', $options);
    }

    /**
     * Adds a select box with multiple options
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 \MUtil\Ra::pairs() name => value array
     */
    public function addMultiSelect($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        return $this->_addToForm($name, 'Multiselect', $options);
    }

    /**
     * Stub for elements where no class should be displayed.
     *
     * @param string $name Name of element
     */
    public function addNone($name)
    {
        return null;
    }

    public function addRadio($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        return $this->_addToForm($name, 'Radio', $options);
    }

    public function addSelect($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        return $this->_addToForm($name, 'Select', $options);
    }

    public function addText($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = $this->_mergeOptions(
            $name,
            Ra::pairs(func_get_args(), 1),
            self::DISPLAY_OPTIONS,
            self::TEXT_OPTIONS
        );

        return $this->_addToForm($name, 'Text', $options);
    }

    public function addTextarea($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,self::DISPLAY_OPTIONS, self::TEXT_OPTIONS, self::TEXTAREA_OPTIONS);

        return $this->_addToForm($name, 'Textarea', $options);
    }

    /**
     * @inheritDoc
     */
    public function getAllowedOptions($key = null)
    {
        if (is_null($key)) {
            return $this->_allowedOptions;
        }

        if (array_key_exists($key, $this->_allowedOptions)) {
            return $this->_allowedOptions[$key];
        } else {
            return array();
        }
    }

    public function getFilterBridge(): FilterBridgeInterface
    {
        if (! isset($this->filterBridge)) {
            // @phpstan-ignore assign.propertyType
            $this->filterBridge = $this->dataModel->getBridgeFor('filter');
        }
        // @phpstan-ignore return.type
        return $this->filterBridge;
    }

    public function getFormatted(string $name) : mixed
    {
        return \Zalt\Late\Late::method($this, 'format', $name, \Zalt\Late\Late::get($name));
    }

    /**
     * @inheritDoc
     */
    public function getLate(string $name) : ?LateCall
    {
        return $this->getFormatted($name);
    }

    /**
     * @inheritDoc
     */
    public function getLateValue(string $name) : mixed
    {
        return Late::raise($this->getFormatted($name));
    }

    /**
     * @inheritDoc
     */
    public function getMode() : int
    {
        // Fixed
        return BridgeInterface::MODE_SINGLE_ROW;
    }

    /**
     * @inheritDoc
     */
    public function getModel() : DataReaderInterface
    {
        return $this->dataModel;
    }

    /**
     * @inheritDoc
     */
    public function getRepeater() : RepeatableInterface
    {
        return $this->dataModel->loadRepeatable();
    }

    /**
     * @inheritDoc
     */
    public function getRow() : mixed
    {
        return $this->dataModel->loadFirst();
    }

    public function getValidatorBridge(): ValidatorBridgeInterface
    {
        if (! isset($this->validatorBridge)) {
            // @phpstan-ignore assign.propertyType
            $this->validatorBridge = $this->dataModel->getBridgeFor('validator');
        }
        // @phpstan-ignore return.type
        return $this->validatorBridge;
    }

    /**
     * @inheritDoc
     */
    public function hasRepeater() : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setAllowedOptions($key, $options)
    {
        if (is_string($options)) {
            $options = array($options);
        }

        $this->_allowedOptions[$key] = $options;
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function setMode(int $mode) : BridgeInterface
    {
        // Do nothing
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setRepeater($repeater) : BridgeInterface
    {
        // Do nothing
        return $this;
    }
}