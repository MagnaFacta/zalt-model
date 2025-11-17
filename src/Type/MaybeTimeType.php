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
     * @var bool Should the time from input data be used or ignored?
     */
    protected bool $editTime = true;

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

        $output['editTime']        = $this->editTime;
        $output['maybeDateFormat'] = $this->maybeDateFormat;
        $output['maybeTimeFormat'] = $this->maybeTimeFormat;
        $output['maybeTimeValue']  = $this->maybeTimeValue;

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

    public function getStringValue($value, $isNew, $name, array $context, MetaModelInterface $metaModel)
    {
        if (! $metaModel->getWithDefault($name, 'editTime', $this->editTime)) {
            if (!$value instanceof DateTimeInterface) {
                $value = self::toDate(
                    $value,
                    $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                    $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                    false);

                if ($value instanceof DateTimeInterface) {
                    $maybeTimeFormat = $metaModel->getWithDefault($name, 'maybeTimeFormat', $this->maybeTimeFormat);
                    $maybeTimeValue  = $metaModel->getWithDefault($name, 'maybeTimeValue', $this->maybeTimeValue);
                    if ($value->format($maybeTimeFormat) != $maybeTimeValue) {
                        if ($value instanceof \DateTimeImmutable) {
                            $value = \DateTime::createFromImmutable($value);
                        }
                        list($hour, $minute, $second) = explode(':', $maybeTimeValue);
                        if ($value instanceof \DateTime) {
                            $value->setTime((int) $hour, (int) $minute, (int) $second);

                            return $value->format($metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat));
                        }
                    }
                }
            }
        }
        // dump($value);

        return parent::getStringValue($value, $isNew, $name, $context, $metaModel);
    }
}