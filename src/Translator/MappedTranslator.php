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
     * @param TranslatorInterface $translator
     * @param string[] $map sourceName => targetName
     */
    public function __construct(
        TranslatorInterface $translator,
        protected array $map = [],
    )
    {
        parent::__construct($translator);
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
}