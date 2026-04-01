<?php

namespace Zalt\Model\Mapping\Database;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class JoinTable
{
    public function __construct(
        public readonly string $tableName,
        public readonly array $joinFields,
        public readonly ?string $alias = null,
        public readonly bool $readonly = true,
        public readonly JoinType $joinType = JoinType::INNER,
        public readonly string|array|null $groups = null,
    ) {}
}