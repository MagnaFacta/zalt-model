<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

use Zalt\Model\Data\DataWriterInterface;
use Zalt\Model\Exception\ModelTranslatorException;

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
     * @throws ModelTranslatorException
     */
    public function getFieldsTranslations(): array;

    /**
     * Returns an array of the field names that are required (for information purposes in the interface)
     *
     * @return array of fields sourceName => targetName
     */
    public function getRequiredFields(): array;

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
     * Set the description.
     *
     * @param string $description A description that enables users to choose the transformer they need.
     * @return ModelTranslatorInterface (continuation pattern)
     */
    public function setDescription(string $description): ModelTranslatorInterface;

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
     * @param mixed $key
     * @return mixed Row array or false when errors occurred
     */
    public function translateRowValues($row, mixed $rowId);

    /**
     * Validate the data against the target form
     *
     * @param array $row
     * @param mixed $key
     * @return mixed Row array or false when errors occurred
     */
    public function validateRowValues(array $row, mixed $rowId);

}