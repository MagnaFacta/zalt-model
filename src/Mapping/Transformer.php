<?php

namespace Zalt\Model\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Transformer
{
    public function __construct(
        public readonly string $class,
        public readonly array $options = [],
        public readonly string|array|null $groups = null,
    )
    {
    }
}