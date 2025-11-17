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
use Zalt\Model\Exception\ModelTranslatorException;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelLoader;
use Zalt\Model\Ra\CsvModel;
use Zalt\Model\Ra\TabbedTextModel;

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
class ImportProcessor implements ImportProcessorInterface
{
    protected ?ModelTranslatorInterface $modelTranslator = null;

    protected ?DataReaderInterface $sourceModel = null;

    protected ?DataWriterInterface $targetModel = null;

    public function __construct(
        protected readonly MetaModelLoader $metaModelLoader,
    )
    { }

    /**
     * @inheritDoc
     */
    public function getImportTranslator(): ?ModelTranslatorInterface
    {
        return $this->modelTranslator;
    }

    /**
     * @inheritDoc
     */
    public function getSourceModel(): ?DataReaderInterface
    {
        return $this->sourceModel;
    }

    /**
     * @inheritDoc
     */
    public function getTargetModel(): ?DataWriterInterface
    {
        return $this->targetModel;
    }

    /**
     * @inheritDoc
     */
    public function setImportTranslator(ModelTranslatorInterface $translator): ImportProcessorInterface
    {
        $this->modelTranslator = $translator;
        return $this;
    }

    /**
     * Set the source model using a filename
     *
     * @param string $filename
     * @param null|string $extension Optional extension if the extension of the file should not be used
     * @return DataReaderInterface
     */
    public function setSourceFile(string $filename, ?string $extension = null): DataReaderInterface
    {
        if (null === $filename) {
            throw new ModelTranslatorException("No filename specified to import");
        }

        if (null === $extension) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
        }

        if (!file_exists($filename)) {
            throw new ModelTranslatorException(sprintf(
                "File '%s' does not exist. Import not possible.",
                $filename
            ));
        }

        // dump($filename);
        $metaModel = $this->metaModelLoader->createMetaModel($filename);
        switch (strtolower($extension)) {
            case 'txt':
                $model = new TabbedTextModel($filename, null, $metaModel);
                break;

            case 'csv':
                $model = new CsvModel($filename, null, $metaModel);
                break;

//            case 'xml':
//                $model = new \MUtil\Model\XmlModel($filename);
//                break;

            default:
                throw new ModelTranslatorException(sprintf(
                    "Unsupported file extension: %s. Import not possible.",
                    $extension
                ));
        }

//        $this->_filename  = $filename;
//        $this->_extension = $extension;
        $this->setSourceModel($model);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function setSourceModel(DataReaderInterface $model): ImportProcessorInterface
    {
        $this->sourceModel = $model;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setTargetModel(DataWriterInterface $model): ImportProcessorInterface
    {
        $this->targetModel = $model;
        return $this;
    }
}