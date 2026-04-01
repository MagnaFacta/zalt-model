<?php

namespace Zalt\Model\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OnLoad
{
    public function __construct(
        public readonly mixed $callable,
        public readonly string|array|null $groups = null,
    )
    {
    }
}