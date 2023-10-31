<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
abstract class AbstractModelType implements ModelTypeInterface
{
    /**
     * @inheritDoc
     */
    public function apply(MetaModelInterface $metaModel, string $name)
    {
        $metaModel->set($name, $this->getSettings());
    }

    /**
     * @inheritDoc
     */
    public function getSetting(string $name): mixed
    {
        $settings = $this->getSettings();
        if (isset($settings[$name])) {
            return $settings[$name];
        }
        return [];
    }
}