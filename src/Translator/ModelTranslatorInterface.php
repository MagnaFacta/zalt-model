<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

use phpDocumentor\Reflection\Types\Scalar;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataWriterInterface;

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
interface ModelTranslatorInterface
{
    /**
     * Returns a description of the translator to enable users to choose
     * the translator they need.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns error messages from the transformation.
     *
     * @return array of String messages
     */
    public function getErrors(): array;

    /**
     * Get information on the field translations
     *
     * @return array of fields sourceName => targetName
     * @throws \MUtil\Model\ModelException
     */
    public function getFieldsTranslations(): array;

    /**
     * Returns an array of the field names that are required
     *
     * @return array of fields sourceName => targetName
     */
    public function getRequiredFields(): array;

    /**
     * Returns a description of the translator errors for the row specified.
     *
     * @param mixed $row
     * @return array of String messages
     */
    public function getRowErrors($row): array;

    /**
     * Get the source model, where the data is coming from.
     *
     * @return DataReaderInterface $sourceModel The source of the data
     */
    public function getSourceModel(): DataReaderInterface;

    /**
     * Get the target model, where the data is going to.
     *
     * @return DataWriterInterface The target of the data
     */
    public function getTargetModel(): DataWriterInterface;

    /**
     * True when the transformation generated errors.
     *
     * @return boolean True when there are errora
     */
    public function hasErrors(): bool;

    /**
     * Set the source model, where the data is coming from.
     *
     * @param DataReaderInterface $sourceModel The source of the data
     * @return ModelTranslatorInterface (continuation pattern)
     */
    public function setSourceModel(DataReaderInterface $sourceModel): ModelTranslatorInterface;

    /**
     * Set the target model, where the data is going to.
     *
     * @param DataWriterInterface $sourceModel The target of the data
     * @return ModelTranslatorInterface (continuation pattern)
     */
    public function setTargetModel(DataWriterInterface $targetModel): ModelTranslatorInterface;

    /**
     * Prepare for the import.
     *
     * @return ModelTranslatorInterface (continuation pattern)
     */
    public function startImport(): ModelTranslatorInterface;

    /**
     * Perform all the translations in the data set.
     *
     * This code does not validate the individual inputs, but does check the ovrall structure of the input
     *
     * @param \Traversable|array $data a nested data set as loaded from the source model
     * @return array|bool Nested row array or false when errors occurred
     */
    public function translateImport($data): mixed;

   /**
     * Perform any translations necessary for the code to work
     *
     * @param mixed $row array or \Traversable row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, $key);

    /**
     * Validate the data against the target form
     *
     * @param array $row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    public function validateRowValues(array $row, $key);

}