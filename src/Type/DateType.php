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
class DateType extends AbstractDateType
{
    public string $dateFormat = 'd-m-Y';
    public string $description = 'dd-mm-yyyy';

    public int $size = 10;

    public string $storageFormat = 'Y-m-d';

    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_DATE;
    }
}