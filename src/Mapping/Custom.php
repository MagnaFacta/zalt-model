<?php

namespace Zalt\Model\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Custom
{
    public function __construct(
        public readonly array $options = [],
        public readonly string|array|null $groups = null,
    )
    {
    }
}