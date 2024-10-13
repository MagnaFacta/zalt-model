<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

use Zalt\Base\TranslatorInterface;
use Zalt\Model\Exception\ModelTranslatorException;

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
class MappedTranslator extends ModelTranslatorAbstract
{
    /**
     * @var array<string, mixed>  Optional map with default fixed values
     */
    protected array $defaultFixed = [];

    /**
     * @var array Optional map to use when none specified in constructor
     */
    protected array $defaultMap = [];

    /**
     * @param TranslatorInterface $translator
     * @param string[] $map sourceName => targetName
     */
    public function __construct(
        TranslatorInterface $translator,
        protected array $map = [],
        protected array $fixed = [],
    )
    {
        parent::__construct($translator);

        if (! $this->map) {
            $this->map = $this->defaultMap;
        }
        if (! $this->fixed) {
            $this->fixed = $this->defaultFixed;
        }
    }

    /**
     * @inheritDoc
     */
    public function getFieldsTranslations(): array
    {
        if ($this->map) {
            return $this->map;
        }

        throw new ModelTranslatorException(sprintf("Map no set befoire use in %s", get_class($this)));
    }

    public function getMap(): array
    {
        return $this->map;
    }

    public function setMap(array $map): MappedTranslator
    {
        $this->map = $map;
        return $this;
    }

    public function translateRowValues($row, mixed $rowId)
    {
        $row += $this->fixed;
        foreach ($this->map as $input => $output) {
            if (isset($row[$input])) {
                $row[$output] = $row[$input];
            }
        }


        return parent::translateRowValues($row, $rowId);
    }
}