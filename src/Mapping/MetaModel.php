<?php

namespace Zalt\Model\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class MetaModel
{
    public function __construct(
        public readonly string $type = 'sqlJoinModel',
    )
    {
    }
}