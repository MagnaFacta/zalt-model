<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;
use Zalt\Model\Mapping\Trans;

#[Attribute(Attribute::TARGET_PARAMETER)]
class SelectElement implements MetaSetterInterface
{
    public string $elementClass = 'select';

    public function __construct(
        public readonly Trans|string|null $label = null,
        public readonly Trans|string|null $description = null,
        public readonly mixed $default = null,
        public readonly bool $disabled = false,
        public readonly bool $readonly = false,
        public readonly array $multiOptions = [],
        public readonly array $multiOptionSettings = [],
        public readonly ?array $elementOptions = null,
        public string|array|null $groups = null,
    )
    {
    }
}