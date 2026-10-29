<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;
use Zalt\Model\Mapping\Trans;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class FormElement implements MetaSetterInterface
{
    public function __construct(
        public readonly Trans|string|null $label = null,
        public readonly ?string $elementClass = null,
        public readonly Trans|string|null $description = null,
        public readonly mixed $default = null,
        public readonly ?bool $disabled = null,
        public readonly ?bool $readonly = null,
        public readonly ?array $elementOptions = null,
        public string|array|null $groups = null,
    ) {}
}