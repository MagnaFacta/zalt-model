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
abstract class AbstractUntypedType extends AbstractModelType
{
    protected int $originalType = MetaModelInterface::TYPE_STRING;

    public function apply(MetaModelInterface $metaModel, string $name)
    {
        $this->originalType = $metaModel->getWithDefault($name, 'type', $this->originalType);

        parent::apply($metaModel, $name);
    }

    public function getBaseType(): int
    {
        return $this->originalType;
    }
}