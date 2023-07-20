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
class DateTimeType extends AbstractDateType
{
    public string $dateFormat = 'd-m-Y H:i';
    public string $description = 'dd-mm-yyyy hh:mm';

    public int $size = 16;

    public string $storageFormat = 'Y-m-d H:i:s';

    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_DATETIME;
    }
}