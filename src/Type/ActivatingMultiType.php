<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class ActivatingMultiType extends AbstractUntypedType
{
    public function __construct(
        protected array $activeLabels,
        protected array $inactiveLabels,
        protected string $className = '',
        protected string $classNo   = 'deleted',
        protected string $classYes  = '',
    )
    {  }

    /**
     * @inheritDoc
     */
    public function apply(MetaModelInterface $metaModel, string $name)
    {
        parent::apply($metaModel, $name);

        if ($this->className) {
            $column = sprintf(
                "CASE WHEN %s IN ('%s') THEN '%s' ELSE '%s' END",
                $name,
                implode("', '", array_keys($this->activeLabels)),
                $this->classYes,
                $this->classNo);

            $metaModel->set($this->className, 'column_expression', $column);
        }
    }

    /**
     * @inheritDoc
     */
    public function getSettings(): array
    {
        $settings = [
            'elementClass' => 'Select',
            'multiOptions' => $this->activeLabels + $this->inactiveLabels,
        ];

        $settings[ActivatingYesNoType::$activatingValue] = array_keys($this->activeLabels);
        $settings[ActivatingYesNoType::$deactivatingValue] = array_keys($this->inactiveLabels);

        return $settings;
    }
}