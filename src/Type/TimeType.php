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
class TimeType extends AbstractDateType
{
    public string $dateFormat = 'H:i';
    public string $description = 'hh:mm';

    public int $size = 6;

    public string $storageFormat = 'H:i:s';

    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_TIME;
    }
}