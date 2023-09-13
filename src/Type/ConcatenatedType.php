<?php

namespace Zalt\Model\Type;

use Zalt\Html\Sequence;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;

class ConcatenatedType extends AbstractModelType
{
    /**
     * The character used to separate values when displaying.
     *
     * @var string
     */
    protected $displaySeperator = ' ';

    /**
     * Optional multi options to use
     *
     * @var array
     */
    protected $options;

    /**
     * The character used to separate values when storing.
     *
     * @var string
     */
    protected $seperatorChar = ' ';

    /**
     * When true the value is padded on both sides with the $seperatorChar.
     *
     * Makes it easier to filter.
     *
     * @var boolean
     */
    protected $valuePad = true;

    /**
     * \MUtil\Ra::args() parameter passing is allowed.
     *
     * @param string $seperatorChar
     * @param string $displaySeperator
     * @param boolean $valuePad
     */
    public function __construct($seperatorChar = ' ', $displaySeperator = ' ', $valuePad = true)
    {
        $args = Ra::args(
            func_get_args(),
            array(
                'seperatorChar' => 'is_string',
                'displaySeperator' => array('\\Zalt\\Html\\HtmlInterface', 'is_string'),
                'valuePad' => 'is_boolean',
            ),
            array('seperatorChar' => ' ', 'displaySeperator' => ' ', 'valuePad' => true)
        );

        $this->seperatorChar    = substr($args['seperatorChar'] . ' ', 0, 1);
        $this->displaySeperator = $args['displaySeperator'];
        $this->valuePad         = $args['valuePad'];
    }

    /**
     * Use this function for a default application of this type to the model
     *
     * @param MetaModelInterface $metaModel
     * @param string $name The field to set the seperator character
     * @return void
     */
    public function apply(MetaModelInterface $metaModel, string $name)
    {
        $metaModel->set($name, 'formatFunction', array($this, 'format'));
        $metaModel->setOnLoad($name, array($this, 'loadValue'));
        $metaModel->setOnSave($name, array($this, 'saveValue'));

        $this->options = $metaModel->get($name, 'multiOptions');
    }


    /**
     * Displays the content
     *
     * @param string $value
     * @return string
     */
    public function format($value)
    {
        // \MUtil\EchoOut\EchoOut::track($value, $this->options);
        if (! is_array($value)) {
            $value = $this->loadValue($value);
        }
        if (is_array($value)) {
            if ($this->options) {
                foreach ($value as &$val) {
                    if (isset($this->options[$val])) {
                        $val = $this->options[$val];
                    }
                }
            }
            if (is_string($this->displaySeperator)) {
                return implode($this->displaySeperator, $value);
            } else {
                $output = new Sequence($value);
                $output->setGlue($this->displaySeperator);
                return $output;
            }
        }
        if (isset($this->options[$value])) {
            return $this->options[$value];
        }
        return $value;
    }

    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_STRING;
    }

    /**
     * If this field is saved as an array value, use
     *
     * @return array Containing settings for model item
     */
    public function getSettings(): array
    {
        $output['formatFunction'] = array($this, 'format');
        $output[MetaModel::LOAD_TRANSFORMER] = array($this, 'loadValue');
        $output[MetaModel::SAVE_TRANSFORMER] = array($this, 'saveValue');

        return $output;
    }

    /**
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return array Of the values
     */
    public function loadValue($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        // \MUtil\EchoOut\EchoOut::track($value, $name, $context);
        if (! is_array($value)) {
            if ($this->valuePad) {
                $value = trim((string)$value, $this->seperatorChar);
            }
            // If it was empty, return an empty array instead of array with an empty element
            if(empty($value)) {
                return [];
            }
            $value = explode($this->seperatorChar, $value);
        }
        // \MUtil\EchoOut\EchoOut::track($value);

        return $value;
    }

    /**
     * A ModelAbstract->setOnSave() function that concatenates the
     * value if it is an array.
     *
     * @see \MUtil\Model\ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string Of the values concatenated
     */
    public function saveValue($value, $isNew = false, $name = null, array $context = array())
    {
        // \MUtil\EchoOut\EchoOut::track($value);
        if (is_array($value)) {
            $value = implode($this->seperatorChar, $value);

            if ($this->valuePad) {
                $value = $this->seperatorChar . $value . $this->seperatorChar;
            }
        }
        return $value;
    }
}