<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class HiddenElement implements MetaSetterInterface
{
    public string $elementClass = 'hidden';

    public function __construct(
        public readonly mixed $default = null,
        public readonly ?bool $disabled = null,
        public readonly ?bool $readonly = null,
        public array|null $elementOptions = null,
        public string|array|null $groups = null,
    )
    {
    }
}