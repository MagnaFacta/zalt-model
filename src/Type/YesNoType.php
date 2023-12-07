<?php

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;

class YesNoType extends AbstractUntypedType
{
    protected int $originalType = MetaModelInterface::TYPE_NUMERIC;

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
        parent::apply($metaModel, $name);

        if ($this->className) {
            $column = sprintf("CASE WHEN %s = 1 THEN '%s' ELSE '%s' END", $name, $this->classYes, $this->classNo);
            $metaModel->set($this->className, 'column_expression', $column);
        }
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