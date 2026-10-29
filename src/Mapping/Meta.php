<?php

namespace Zalt\Model\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Meta
{
    public function __construct(
        public readonly string $key,
        public readonly mixed $value,
        public readonly string|array|null $groups = null,
    )
    {
    }
}