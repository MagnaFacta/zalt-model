<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

use \DateTimeInterface;
use \DateTimeImmutable;
use Laminas\Validator\ValidatorInterface;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Bridge\ValidatorBridgeInterface;
use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\Exception\ModelTranslatorException;
use Zalt\Model\MetaModelInterface;
use Zalt\Ra\Ra;

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
abstract class ModelTranslatorAbstract implements ModelTranslatorInterface
{
    use TranslateableTrait;

    /**
     * Local copy of keys of getFieldsTranslation() for speedup.
     *
     * Set by startImport().
     *
     * @var array
     */
    private $_fieldKeys = array();

    /**
     * Local copy of getFieldsTranslation() for speedup.
     *
     * Set by startImport().
     *
     * @var array
     */
    private $_fieldMap = array();

    /**
     * Is mapping of fieldnames required.
     *
     * (Yes unless all names are the same, as in StraightTranslator.)
     *
     * Set by startImport().
     *
     * @var boolean
     */
    private ?bool $_mapRequired = null;

    /**
     * @var array [fieldname => [validatos]]
     */
    private $_validators = [];

    /**
     * Date import formats
     *
     * @var array
     */
    public array $dateFormats = ['Y-m-d'];

    /**
     * Datetime import formats
     *
     * @var array
     */
    public array $datetimeFormats = ['Y-m-d H:i:s'];

    protected string $description = '';

    /**
     * @var array $rowId => [errors]
     */
    protected array $errors = array();

    /**
     * The string value used for NULL values
     *
     * @var string Uppercase string
     */
    public string $nullValue = 'NULL';

    protected DataWriterInterface $targetModel;

    /**
     * Time import formats
     *
     * @var array
     */
    public array $timeFormats = ['H:i:s'];

    /**
     * @var bool Enable/disable row field validation
     */
    public bool $validateInput = true;

    public function __construct(
        TranslatorInterface $translator,
    )
    {
        $this->translate = $translator;
    }

    protected function addError($rowId, $field, string $error)
    {
        $this->errors[$rowId][$field][] = $error;
    }

    protected function addErrors($rowId, $field, array $errors)
    {
        foreach ($errors as $error) {
            $this->addError($rowId, $field, $error);
        }
    }

    /**
     * Translate textual null values to actual PHP nulls and trim any whitespace
     *
     * @param mixed $value
     * @param scalar $key The array key, optionally a model key as well
     * @return mixed
     */
    public function filterBasic(&$value, $key)
    {
        if (is_string($value) && ($this->nullValue === strtoupper($value))) {
            $value = null;
            return;
        }

        $metaModel = $this->targetModel->getMetaModel();

        if ($metaModel->is($key, 'type', MetaModelInterface::TYPE_DATE)) {
            $formats = $this->dateFormats;
        } elseif ($metaModel->is($key, 'type', MetaModelInterface::TYPE_DATETIME)) {
            $formats = $this->datetimeFormats;
        } elseif ($metaModel->is($key, 'type', MetaModelInterface::TYPE_TIME)) {
            $formats = $this->timeFormats;
        } else {
            $formats = false;
        }

        if ($formats) {
            if ($value && is_string($value) && (! $value instanceof DateTimeInterface)) {
                $date = false;
                foreach ($formats as $format) {
                    $date = DateTimeImmutable::createFromFormat($format, trim($value));
                    if ($date) {
                        $value = $date;
                        return;
                    }
                }
            }
            return;
        }

        $options = $metaModel->get($key, 'multiOptions');
        if ($options && (! isset($options[$value])) && in_array($value, $options)) {
            $value = array_search($value, $options);
        }

        if (is_string($value)) {
            $value = trim($value);
            return;
        }

        return;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        $output = [];
        foreach ($this->errors as $rowId => $rowErrors) {
            $rowErrors = $this->getTranslatedRowErrors($rowId, $rowErrors);

            if ($rowErrors) {
                $output[] = $rowErrors;
            }
        }
        return Ra::flatten($output);
    }

    // public function getFieldsTranslations(): array

    /**
     * @inheritDoc
     */
    public function getRequiredFields(): array
    {
        $trans  = $this->getFieldsTranslations();
        $keys   = array_fill_keys($this->targetModel->getMetaModel()->getKeys(), true);

        $output = [];
        foreach ($trans as $input => $source) {
            if (isset($keys[$source])) {
                $output[$input] = $source;
            }
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getTargetModel(): DataWriterInterface
    {
        return $this->targetModel;
    }

    /**
     * Returns a description of the translator errors for the row specified.
     *
     * @param mixed $rowId
     * @param array $rowErrors
     * @return array of String messages
     */
    protected function getTranslatedRowErrors($rowId, array $rowErrors): array
    {
        $errorOutput = [];
        $start = sprintf($this->_('Row %s'), $rowId);
        foreach ($rowErrors as $field => $errorMessages) {
            if (is_numeric($field)) {
                $middle = '';
            } else {
                $middle = sprintf($this->_(' field %s'), $field);
            }
            $prefix =  $start . $middle . $this->_(': ');
            foreach ((array) $errorMessages as $field2 => $error) {
                $errorOutput[] = $prefix . $error;
            }
        }
        return $errorOutput;
    }

    public function getValidators(): array
    {
        $metaModel       = $this->targetModel->getMetaModel();
        $metaModelLoader = $metaModel->getMetaModelLoader();
        /**
         * @var ValidatorBridgeInterface $validatorBridge
         */
        $validatorBridge = $metaModelLoader->createBridge('validator', $this->targetModel);

        $output = [];
        foreach ($this->_fieldMap as $targetName) {
            $output[$targetName] = $validatorBridge->getValidatorsFor($targetName);
        }

        // Remove empty validators
        return array_filter($output);
    }

    /**
     * @inheritDoc
     */
    public function hasErrors(): bool
    {
        return (boolean) $this->errors;
    }

    /**
     * Default preparation for row import.
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $rowId
     * @return array|bool
     * @throws ModelTranslatorException
     */
    protected function prepareRow($row, $rowId)
    {
        if (null === $this->_mapRequired) {
            throw new ModelTranslatorException("Trying to translate without call to startImport().");
        }

        if ($row instanceof \Traversable) {
            $row = iterator_to_array($row);
        } elseif (! is_array($row)) {
            $row = Ra::to($row, Ra::RELAXED);
        }

        if (! (is_array($row) && $row)) {
            // Do not bother with non array data
            $this->addError($rowId, 0, "Input is not an array");
            return false;
        }

        $rowMap = array_intersect($this->_fieldKeys, array_keys($row));
        if (! $rowMap) {
            $this->addError($rowId, 0, $this->_("No field overlap between source and target"));
            return false;
        }

        if ($this->_mapRequired) {
            // This does keep the original values. That is intentional.
            foreach ($rowMap as $source) {
                if (array_key_exists($source, $row)) {
                    $row[$this->_fieldMap[$source]] = $row[$source];
                }
            }
        }

        return $row;
    }

    public function saveAll(array $rows): array
    {
        $output = [];
        foreach ($rows as $rowId => $row) {
            $output[$rowId] = $this->targetModel->save($row);
        }
        return $output;
    }

    /**
     * Set the description.
     *
     * @param string $description A description that enables users to choose the transformer they need.
     * @return ModelTranslatorInterface (continuation pattern)
     */
    public function setDescription(string $description): ModelTranslatorInterface
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setTargetModel(DataWriterInterface $targetModel): ModelTranslatorInterface
    {
        $this->targetModel = $targetModel;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function startImport(): ModelTranslatorInterface
    {
        if (! isset($this->targetModel)) {
            throw new ModelTranslatorException("Trying to start an import without target model.");
        }

        // Clear errors
        $this->errors = [];

        $this->_fieldMap    = $this->getFieldsTranslations();
        $this->_fieldKeys   = array_keys($this->_fieldMap);
        $this->_mapRequired = $this->_fieldKeys !== array_values($this->_fieldMap);

        // Make sure the validators are set (unless overruled by child class)
        if ($this->validateInput) {
            $this->_validators = $this->getValidators();
        } else {
            $this->_validators = [];
        }

        return $this;
    }

    public function translateImport($data): mixed
    {
        $this->startImport();

        $output = array();

        foreach ($data as $key => $row) {
            $row = $this->translateRowValues($row, $key);

            if ($this->validateInput && $row) {
                $row = $this->validateRowValues($row, $key);
            }

            if ($row) {
                $output[$key] = $row;
            }
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function translateRowValues($row, mixed $rowId)
    {
        $row = $this->prepareRow($row, $rowId);

        if ($row) {
            array_walk($row, array($this, 'filterBasic'));
        }

        return $row;
    }

    /**
     * @inheritDoc
     */
    public function validateRowValues(array $row, mixed $rowId)
    {
        $valid = true;

        foreach ($this->_validators as $field => $validators) {
            $value = $row[$field] ?? '';
            foreach ($validators as $validator) {
                if ($validator instanceof ValidatorInterface) {
                    // @phpstan-ignore arguments.count
                    if (! $validator->isValid($value, $row)) {
                        $valid = false;
                        $this->addErrors($rowId, $field, $validator->getMessages());
                    }
                }
            }
        }

        if ($valid) {
            return $row;
        }

        return false;
    }
}