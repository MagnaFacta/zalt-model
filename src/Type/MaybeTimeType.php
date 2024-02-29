<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use DateTimeInterface;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class MaybeTimeType extends DateTimeType
{
    /**
     * @var string The format to use when the time is equal to the $maybeTimeValue
     */
    protected string $maybeDateFormat = 'd-m-Y';

    /**
     * @var string The format used to compare to the $maybeTimeValue
     */
    protected string $maybeTimeFormat = 'H:i:s';

    /**
     * @var string The time that should not be displayed
     */
    protected string $maybeTimeValue = '00:00:00';

    public function getSettings(): array
    {
        $output = parent::getSettings();

        $output['maybeDateFormat'] = $this->maybeDateFormat;
        $output['maybeTimeFormat'] = $this->maybeTimeFormat;
        $output['maybeTimeValue'] = $this->maybeTimeValue;

        return $output;
    }

    public function format($value, string $name, MetaModelInterface $metaModel)
    {
        if (! $value instanceof DateTimeInterface) {
            $value = self::toDate(
                $value,
                $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                false);
        }
        if ($value instanceof DateTimeInterface) {
            if ($value->format($this->maybeTimeFormat) == $this->maybeTimeValue) {
                return $value->format($this->maybeDateFormat);
            }
            return $value->format($metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat));
        }
        if (! $value) {
            return $this->getNullDisplayValue($name, $metaModel);
        }

        return $value;
    }

}