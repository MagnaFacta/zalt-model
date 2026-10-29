<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;
use Zalt\Model\Mapping\Trans;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class DateElement implements MetaSetterInterface
{
    public string $elementClass = 'date';

    public function __construct(
        public readonly Trans|string|null $label = null,
        public readonly Trans|string|null $description = null,
        public readonly mixed $default = null,
        public readonly ?bool $disabled = null,
        public readonly ?bool $readonly = null,
        public readonly string|null $format = null,
        public array|null $elementOptions = null,
        public string|array|null $groups = null,
    )
    {
    }
}