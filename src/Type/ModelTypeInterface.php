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
     * Get a general (not field adapted) setting for this type
     *
     * @param string $name
     * @return array Containing settings for model item
     */
    public function getSetting(string $name): mixed;

    /**
     * Get the general (not field adapted) settings for this type
     *
     * @return array Containing settings for model item
     */
    public function getSettings(): array;
}