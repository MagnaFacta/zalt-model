<?php

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;

class YesNoType extends AbstractModelType
{
    public function __construct(
        protected array $labels,
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
        $metaModel->set($name, $this->getSettings());

        if ($this->className) {
            $column = sprintf("CASE WHEN %s = 1 THEN '%s' ELSE '%s' END", $name, $this->classYes, $this->classNo);
            $metaModel->set($this->className, 'column_expression', $column);
        }
    }

    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_NUMERIC;
    }

    /**
     * @inheritDoc
     */
    public function getSettings(): array
    {
        return [
            'elementClass' => 'CheckBox',
            'multiOptions' => $this->labels,
        ];
    }
}