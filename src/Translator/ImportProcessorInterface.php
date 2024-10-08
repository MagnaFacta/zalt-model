<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\DataWriterInterface;

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
interface ImportProcessorInterface
{
    /**
     * @return ModelTranslatorInterface|null
     */
    public function getImportTranslator(): ?ModelTranslatorInterface;

    /**
     * @return DataReaderInterface|null
     */
    public function getSourceModel(): ?DataReaderInterface;

    /**
     * @return DataWriterInterface|null
     */
    public function getTargetModel(): ?DataWriterInterface;

    /**
     * @param ModelTranslatorInterface $translator
     * @return ImportProcessorInterface
     */
    public function setImportTranslator(ModelTranslatorInterface $translator): ImportProcessorInterface;

    /**
     * Set the source model using a filename
     *
     * @param string $filename
     * @param null|string $extension Optional extension if the extension of the file should not be used
     * @return DataReaderInterface
     */
    public function setSourceFile(string $filename, ?string $extension = null): DataReaderInterface;

    /**
     * @param DataReaderInterface $model
     * @return ImportProcessorInterface
     */
    public function setSourceModel(DataReaderInterface $model): ImportProcessorInterface;

    /**
     * @param DataWriterInterface $model
     * @return ImportProcessorInterface
     */
    public function setTargetModel(DataWriterInterface $model): ImportProcessorInterface;
}