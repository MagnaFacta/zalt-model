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
        protected string|null $className = null,
        protected string $classNo   = 'deleted',
        protected string $classYes  = '',
    )
    {  }

    /**
     * @inheritDoc
     */
    public function apply(MetaModelInterface $metaModel, string $name): void
    {
        parent::apply($metaModel, $name);

        $this->applyClass($metaModel, $name);
    }

    /**
     * Apply active or deactivated class names to model
     *
     * @param MetaModelInterface $metaModel
     * @param string $name
     * @return void
     */
    public function applyClass(MetaModelInterface $metaModel, string $name): void
    {
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