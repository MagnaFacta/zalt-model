<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge\Laminas;

use Laminas\Validator\Digits;
use Laminas\Validator\InArray;
use Laminas\Validator\LessThan;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;
use Laminas\Validator\ValidatorInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Bridge\ValidatorBridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\MetaModelException;
use Zalt\Model\Exception\ModelValidatorLoadException;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\ModelAwareInterface;
use Zalt\Model\ModelFieldNameAwareInterface;

/**
 * A validator added can be added as a:
 *
 * - A single class name
 * - An array of [string: className, bool breakChainOnFailure, array options]
 * - An instantiated object implementing the Laminas ValidatorInterface
 *
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @since      Class available since version 1.0
 */
class LaminasValidatorBridge extends \Zalt\Model\Bridge\BridgeAbstract implements ValidatorBridgeInterface
{
    use LaminasElementClassRetrieverTrait;

    /**
     * @var array elementClassName => compile function
     */
    protected array $_elementClassCompilers = [];

    /**
     * @var array typeIdentifyer => compile function
     */
    protected array $_typeClassCompilers = [];

    /**
     * @var array name => array of validators
     */
    protected array $_loadedValidators = [];

    /**
     * @var array Used by LaminasValidatorLoaderTrait copied functions
     */
    protected array $_validators;

    /**
     * When no size is set for a text-element, the size will be set to the minimum of the
     * maxsize and this value.
     *
     * @var int
     */
    public $defaultTextSize = 40;

    protected ProjectOverloader $validatorOverloader;

    public function __construct(DataReaderInterface $dataModel, ProjectOverloader $projectOverloader = null)
    {
        parent::__construct($dataModel);

        if (! $this->dataModel instanceof FullDataInterface) {
            throw new MetaModelException("Only FullDataInterface objects are allowed as input for a " . __CLASS__ . " constructor");
        }

        if (! $projectOverloader instanceof ProjectOverloader) {
            throw new MetaModelException("A ProjectOverloader objects is required as input for a " . __CLASS__ . " constructor");
        }

        $this->validatorOverloader = $projectOverloader->createSubFolderOverloader('Validator');
        $this->validatorOverloader->legacyClasses = false;

        $this->loadDefaultElementCompilers();
        $this->loadDefaultTypeCompilers();
    }

    /**
     * @inheritDoc
     */
    protected function _compile(string $name): array
    {
        return $this->getValidatorsFor($name);
    }

    /**
     * Lazy-load a validator
     *
     * @param  array $validator Validator definition
     * @return ValidatorInterface
     */
    protected function _loadValidator(array $validator): ValidatorInterface
    {
        $origName = $validator['validator'];
        $name     = $this->validatorOverloader->find($validator['validator']);

        $messages = false;
        if (isset($validator['options']) && array_key_exists('messages', (array) $validator['options'])) {
            $messages = $validator['options']['messages'];
            unset($validator['options']['messages']);
        }

        if (empty($validator['options'])) {
            $instance = new $name;
        } else {
            $r = new \ReflectionClass($name);
            if ($r->hasMethod('__construct')) {
                $numeric = false;
                if (is_array($validator['options'])) {
                    $keys    = array_keys($validator['options']);
                    foreach($keys as $key) {
                        if (is_numeric($key)) {
                            $numeric = true;
                            break;
                        }
                    }
                }

                if ($numeric) {
                    $instance = $r->newInstanceArgs((array) $validator['options']);
                } else {
                    $instance = $r->newInstance($validator['options']);
                }
            } else {
                $instance = $r->newInstance();
            }
        }

        if ($messages) {
            if (is_array($messages)) {
                $instance->setMessages($messages);
            } elseif (is_string($messages)) {
                $instance->setMessage($messages);
            }
        }
        $instance->zfBreakChainOnFailure = $validator['breakChainOnFailure'];

        return $instance;
    }

    /**
     * Add validator to validation chain
     *
     * Note: will overwrite existing validators if they are of the same class.
     *
     * @param  string|ValidatorInterface $validator
     * @param  bool $breakChainOnFailure
     * @param  array $options
     * @return self
     * @throws ModelValidatorLoadException if invalid validator type
     */
    public function addValidator($validator, $breakChainOnFailure = false, $options = [])
    {
        if ($validator instanceof ValidatorInterface) {
            $name = get_class($validator);
        } elseif (is_string($validator)) {
            $name      = $validator;
            $validator = [
                'validator' => $validator,
                'breakChainOnFailure' => $breakChainOnFailure,
                'options'             => $options,
            ];
        } else {
            throw new ModelValidatorLoadException('Invalid validator provided to addValidator; must be string or \\Laminas\\Validator\\ValidatorInterface');
        }

        $this->_validators[$name] = $validator;

        return $this;
    }

    /**
     * Add multiple validators
     *
     * @param  array $validators
     * @return void
     */
    protected function _addValidators(array $validators): void
    {
        foreach ($validators as $validatorInfo) {
            if (is_string($validatorInfo)) {
                $this->addValidator($validatorInfo);
            } elseif ($validatorInfo instanceof ValidatorInterface) {
                $this->addValidator($validatorInfo);
            } elseif (is_array($validatorInfo)) {
                $argc                = count($validatorInfo);
                $breakChainOnFailure = false;
                $options             = [];
                if (isset($validatorInfo['validator'])) {
                    $validator = $validatorInfo['validator'];
                    if (isset($validatorInfo['breakChainOnFailure'])) {
                        $breakChainOnFailure = $validatorInfo['breakChainOnFailure'];
                    }
                    if (isset($validatorInfo['options'])) {
                        $options = $validatorInfo['options'];
                    }
                    $this->addValidator($validator, $breakChainOnFailure, $options);
                } else {
                    switch (true) {
                        case (0 == $argc):
                            break;
                        case (1 <= $argc):
                            $validator  = array_shift($validatorInfo);
                            if (2 <= $argc) {
                                $breakChainOnFailure = array_shift($validatorInfo);
                            }
                            if (3 <= $argc) {
                                $options = array_shift($validatorInfo);
                            }
                            $this->addValidator($validator, $breakChainOnFailure, $options);
                            break;
                    }
                }
            } else {
                throw new ModelValidatorLoadException('Invalid validator passed to addValidators() ' . get_class($validatorInfo));
            }
        }
    }

    /**
     * Retrieve all validators for an element
     *
     * @param string $name
     * @return array validator name => validator or array for loading
     */
    public function gatherValidatorsFor(string $name): array
    {
        $validators = $this->metaModel->get($name, 'validators') ?? [];

        if ($validator = $this->metaModel->get($name, 'validator')) {
            if ($validators) {
                array_unshift($validators, $validator);
            } else {
                $validators = array($validator);
            }
        }

        $required = $this->metaModel->get($name, 'required');

        if (! isset($validators[InArray::class])) {
            $options = $this->metaModel->get($name, 'multiOptions');
            if ($options) {
                if ($required) {
                    // Testting NotEmpty no longer needed.
                    $required = false;
                } else {
                    $options[''] = null;
                }
                $validators[InArray::class] = [InArray::class, false, ['haystack' => array_keys($options)]];
            }
        }
        if ($required && (! $this->metaModel->is($name, 'autoInsertNotEmptyValidator', false)) && (! $validators[NotEmpty::class])) {
            $validators[NotEmpty::class] = NotEmpty::class;
        }

        if (! $this->metaModel->get($name, 'ignoreElementValidators')) {
            $elementClass = $this->getElementClassFor($name);
            if (isset($this->_elementClassCompilers[$elementClass])) {
                $validators += call_user_func($this->_elementClassCompilers[$elementClass], $this->metaModel, $name);
            }
        }
        if (! $this->metaModel->get($name, 'ignoreTypeValidators')) {
            $typeId = $this->metaModel->get($name, 'type');
            if ($typeId && isset($this->_typeClassCompilers[$typeId])) {
                $validators += call_user_func($this->_typeClassCompilers[$typeId], $this->metaModel, $name);
            }
        }

        return $validators;
    }

    /**
     * @param MetaModelInterface $metaModel
     * @param string $name
     * @return array
     */
    public function getElementValidatorsPassword(MetaModelInterface $metaModel, string $name): array
    {
        $output = $this->getElementValidatorsText($metaModel, $name);

        $confirmWith = $metaModel->get($name, 'confirmWith');
        if ($confirmWith) {
            $label = $metaModel->getWithDefault($confirmWith, 'label', null);
            $output['IsConfirmed'] = ['IsConfirmed', false, [$confirmWith, $label]];
        }

        return $output;
    }

    public function getElementValidatorsText(MetaModelInterface $metaModel, string $name): array
    {
        $output = [];

        $stringlength = [];
        if ($metaModel->has($name, 'minlength')) {
            $stringlength['min'] = intval($metaModel->get($name, 'minlength'));
        }
        if ($metaModel->has($name,'maxlength')) {
            $stringlength['max'] = intval($metaModel->get($name,'maxlength'));
            if (! $metaModel->has($name,'size')) {
                $metaModel->set($name, 'size', min($stringlength['max'], $this->defaultTextSize));
            }

        } elseif ($metaModel->has($name,'size')) {
            $stringlength['maxlength'] = $metaModel->get($name, 'size');
        }
        if ($stringlength) {
            $type = $metaModel->get($name, 'type');
            if ($type === MetaModelInterface::TYPE_STRING) {
                $output[StringLength::class] = [StringLength::class, false, $stringlength];
            }
            if ($type === MetaModelInterface::TYPE_NUMERIC) {
                if ($stringlength['max']) {
                    $maxSize = (int)1 . str_repeat('0', $stringlength['max']);
                    $output[LessThan::class] = [LessThan::class, false, ['max' => $maxSize]];
                }
            }
        }

        return $output;
    }

    public function getElementValidatorsTextarea(MetaModelInterface $metaModel, string $name): array
    {
        $output = [];

        if ($metaModel->has($name, 'minlength')) {
            $output[StringLength::class] = [StringLength::class, false, [
                'min' => intval($metaModel->get($name, 'minlength')),
                ]];
        }

        return $output;
    }

    public function getTypeValidatorsNumeric(MetaModelInterface $metaModel, string $name): array
    {
        $output = [];

        $decimals = $metaModel->getWithDefault($name, 'decimals', 0);
        if ($metaModel->get($name, 'unsigned')) {
            if ($decimals) {
            } else {
                $output['Digits'] = [Digits::class];
            }
        } else {

        }

        return $output;
    }

    /**
     * Retrieve all validators for an element
     *
     * @param string $name
     * @return array
     */
    public function getValidatorsFor(string $name): array
    {
        if (isset($this->_loadedValidators[$name])) {
            return $this->_loadedValidators[$name];
        }

        $validators = $this->gatherValidatorsFor($name);

        $this->_loadedValidators[$name] = $this->loadValidators($name, $validators);

        return $this->_loadedValidators[$name];
    }

    protected function loadDefaultElementCompilers()
    {
        // $this->setElementClassCompiler('File', [$this, 'getElementValidatorsFile'])
        $this->setElementClassCompiler('Password', [$this, 'getElementValidatorsPassword'])
            ->setElementClassCompiler('Text', [$this, 'getElementValidatorsText'])
            ->setElementClassCompiler('Textarea', [$this, 'getElementValidatorsTextarea']);
    }

    protected function loadDefaultTypeCompilers()
    {
        $this->setTypeClassCompiler(MetaModelInterface::TYPE_NUMERIC, [$this, 'getTypeValidatorsNumeric']);
    }

    protected function loadValidators(string $name, array $validators)
    {
        $output = [];
        if ($validators) {
            $this->_validators = [];
            $this->_addValidators($validators);

            foreach ($this->_validators as $key => $value) {
                if ($value instanceof ValidatorInterface) {
                    $output[$key] = $value;
                } else {
                    $output[$key] = $this->_loadValidator($value);
                }

                if ($output[$key] instanceof ModelFieldNameAwareInterface) {
                    $output[$key]->setName($name);
                }
                if ($output[$key] instanceof ModelAwareInterface) {
                    $output[$key]->setDataModel($this->dataModel);
                }
            }
        }

        return $output;
    }

    /**
     * @inheritdoc
     */
    public function setElementClassCompiler(string $elementClassName, callable $callable): ValidatorBridgeInterface
    {
        $this->_elementClassCompilers[$elementClassName] = $callable;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setTypeClassCompiler(int $typeId, callable $callable): ValidatorBridgeInterface
    {
        $this->_typeClassCompilers[$typeId] = $callable;
        return $this;
    }
}