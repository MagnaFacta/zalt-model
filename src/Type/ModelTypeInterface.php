<?php

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;

interface ModelTypeInterface
{
    /**
     * Use this function for a default application of this type to the model
     *
     * @param MetaModelInterface $model
     * @param string $name The field to set the seperator character
     * @return void
     */
    public function apply(MetaModelInterface $metaModel, string $name);

    public function getBaseType(): int;

    /**
     * If this field is saved as an array value, use
     *
     * @return array Containing settings for model item
     */
    public function getSettings(): array;
}