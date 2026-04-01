<?php

namespace Zalt\Model\Mapping\Database;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        public readonly string $name,
        public readonly bool $readonly = false,
        public readonly string|null $prefix = null,
    )
    {
    }
}