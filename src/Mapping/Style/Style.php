<?php

namespace Zalt\Model\Mapping\Style;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Style implements MetaSetterInterface
{
    public function __construct(
        public readonly ?string $class = null,
        public readonly ?string $style = null,
    )
    {
    }
}