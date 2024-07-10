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
     * @return DataReaderInterface|null
     */
    public function getTargetModel(): ?DataReaderInterface;

    /**
     * @param ModelTranslatorInterface $translator
     * @return ImportProcessorInterface
     */
    public function setImportTranslator(ModelTranslatorInterface $translator): ImportProcessorInterface;

    /**
     * @param DataReaderInterface $model
     * @return ImportProcessorInterface
     */
    public function setSourceModel(DataReaderInterface $model): ImportProcessorInterface;

    /**
     * @param DataReaderInterface $model
     * @return ImportProcessorInterface
     */
    public function setTargetModel(DataWriterInterface $model): ImportProcessorInterface;
}