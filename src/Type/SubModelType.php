<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class SubModelType extends AbstractModelType
{
    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_CHILD_MODEL;
    }

    /**
     * @inheritDoc
     */
    public function getSettings(): array
    {
        return [
            SqlRunnerInterface::NO_SQL => true,
        ];
    }
}