<?php

namespace Zalt\Model\Mapping\Form;

use Attribute;
use Zalt\Model\Mapping\MetaSetterInterface;
use Zalt\Model\Mapping\Trans;

#[Attribute(Attribute::TARGET_PARAMETER)]
class HtmlElement implements MetaSetterInterface
{
    public string $elementClass = 'html';

    public function __construct(
        public readonly Trans|string|null $label = null,
        public array|null $elementOptions = null,
        public string|array|null $groups = null,
    )
    {
    }
}