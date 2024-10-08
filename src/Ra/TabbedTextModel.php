<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Ra;

use Zalt\Iterator\TextFileIterator;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @since      Class available since version 1.0
 */
class TabbedTextModel extends ArrayModelAbstract
{
    /**
     * The regular expression for split
     *
     * @var string
     */
    protected string $split = "\t";

    public function __construct(
        protected readonly string $fileName,
        protected readonly ?string $encoding,
        MetaModelInterface $metaModel
    )
    {
        parent::__construct($metaModel);
    }

    /**
     * @inheritDoc
     */
    protected function _loadAll(): array
    {
        $iterator = new TextFileIterator($this->fileName, $this->split, $this->encoding);

        // Store the positions in the model
        foreach ($iterator->getFieldMap() as $pos => $name) {
            $this->metaModel->set($name, 'read_position', $pos);
        }

        return iterator_to_array($iterator);
    }
}