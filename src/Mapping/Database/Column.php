<?php

namespace Zalt\Model\Mapping\Database;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class Column
{
    public function __construct(
        public readonly string|null $name = null,
        public readonly string|null $prefix = null,
        public readonly string|null $table = null,
        public readonly string|array|null $groups = null,
    )
    {
    }
}