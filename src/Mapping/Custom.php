<?php

namespace Zalt\Model\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class Custom implements MetaSetterInterface
{
    public function __construct(
        public readonly array $options = [],
        public readonly string|array|null $groups = null,
    )
    {
    }
}