<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Model\MetaModelInterface;
use Zalt\Validator\Model\IsDateModelValidator;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
abstract class AbstractDateType extends AbstractModelType
{
    public static array $databaseConstants = ['CURRENT_TIMESTAMP', 'CURRENT_TIME', 'CURRENT_DATE', 'NOW'];

    public string $dateFormat;

    public string $description;

    protected MetaModelInterface $metaModel;

    public int $size;
    public string $storageFormat;

    /**
     * @inheritDoc
     */
    public function apply(MetaModelInterface $metaModel, string $name)
    {
        $this->metaModel = $metaModel;

        $metaModel->set($name, $this->getSettings());

        $metaModel->setOnLoad($name, [$this, 'getDateTimeValue']);
        $metaModel->setOnSave($name, [$this, 'getStringValue']);
    }

    /**
     * Allow easy overriding
     *
     * @return array Optional
     */
    protected function getExtraSettings(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getSettings(): array
    {
        $output = [
            'dateFormat'    => $this->dateFormat,
            'description'   => $this->description,
            'size'          => $this->size,
            'storageFormat' => $this->storageFormat,
        ];

        return $output + $this->getExtraSettings();
    }

    /**
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return mixed The value to use instead
     */
    public function getDateTimeValue(mixed $value, bool $isNew = false, string $name = null, array $context = array(), bool $isPost = false)
    {
        if ($name) {
            return $this->toDate(
                $value,
                $this->metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $this->metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                $isPost);
        }

        return $this->toDate($value, $this->storageFormat, $this->dateFormat, $isPost);
    }

    /**
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string
     */
    public function getStringValue($value, $isNew = false, $name = null, array $context = array())
    {
        if ($name) {
            $this->toString($value, $this->metaModel->get($name, 'storageFormat'), $this->metaModel->get($name, 'dateFormat'));
        }

        return $this->toString($value, $this->storageFormat, $this->dateFormat);
    }

    public function toDate($value, string $storageFormat, string $dateFormat, bool $isPost = true): mixed
    {
        if ((null === $value) || ($value instanceof \DateTimeImmutable)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (is_string($value) && in_array(strtoupper(rtrim($value, '()')), self::$databaseConstants)) {
            return new \DateTimeImmutable();
        }

        try {
            if ($isPost) {
                // First try dateFormat when posting
                $dateTime = \DateTimeImmutable::createFromFormat($dateFormat, $value);

                if ($dateTime) {
                    return $dateTime;
                }
            }

            // Second try or first when loading
            $dateTime = \DateTimeImmutable::createFromFormat($storageFormat, $value);
            if ($dateTime) {
                return $dateTime;
            }

            // Well we tried
            return new \DateTimeImmutable($value);
        } catch (\Throwable $error) {
            return $value;
        }
    }

    public function toString($value, string $storageFormat, string $dateFormat, bool $isPost = true): ?string
    {
        if ((null === $value) || ('' == $value)) {
            return null;
        }

        if (is_string($value) && in_array(strtoupper(rtrim($value, '()')), self::$databaseConstants)) {
            return date($storageFormat);
        }

        if (! $value instanceof \DateTimeInterface) {
            $value = $this->toDate($value, $storageFormat, $dateFormat, $isPost);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format($storageFormat);
        }

        return $value;
    }
}