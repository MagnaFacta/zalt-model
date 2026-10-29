<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;
use Zalt\Model\Mapping\Trans;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class RTEElement implements MetaSetterInterface
{
    public string $elementClass = 'rte';

    public function __construct(
        public readonly Trans|string|null $label = null,
        public readonly Trans|string|null $description = null,
        public readonly mixed $default = null,
        public readonly ?bool $disabled = null,
        public readonly ?bool $readonly = null,
        public readonly int|null $rows = null,
        public readonly int|null $cols = null,
        public string|array|null $groups = null,
    )
    {
    }
}