<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;
use Zalt\Model\Mapping\Trans;

#[Attribute(Attribute::TARGET_PARAMETER)]
class TextAreaElement implements MetaSetterInterface
{
    public string $elementClass = 'textarea';

    public function __construct(
        public readonly Trans|string|null $label = null,
        public readonly Trans|string|null $description = null,
        public readonly mixed $default = null,
        public readonly bool $disabled = false,
        public readonly bool $readonly = false,
        public readonly int|null $minLength = null,
        public readonly int|null $maxLength = null,
        public readonly int|null $rows = null,
        public readonly int|null $cols = null,
        public string|array|null $groups = null,
    )
    {
    }
}