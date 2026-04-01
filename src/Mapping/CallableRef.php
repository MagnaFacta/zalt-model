<?php

namespace Zalt\Model\Mapping;

class CallableRef
{
    public function __construct(
        public readonly string $className,
        public readonly string $methodName,
    )
    {
    }
}