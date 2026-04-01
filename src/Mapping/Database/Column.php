<?php

namespace Zalt\Model\Mapping\Database;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Column implements MetaSetterInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string|null $prefix = null,
        public readonly string|null $table = null,
        public readonly string|array|null $groups = null,
    )
    {
    }
}