<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class HiddenElement implements MetaSetterInterface
{
    public string $elementClass = 'hidden';

    public function __construct(
        public readonly mixed $default = null,
        public readonly bool $disabled = false,
        public readonly bool $readonly = false,
        public array|null $elementOptions = null,
        public string|array|null $groups = null,
    )
    {
    }
}