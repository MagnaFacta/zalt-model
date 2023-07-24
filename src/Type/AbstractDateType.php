<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Html\Html;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
abstract class AbstractDateType extends AbstractModelType
{
    /**
     * Just to be able to use code completion, but also just in case you want to change the
     */
    public static string $whenDateEmptyKey = 'whenDateEmpty';
    public static string $whenDateEmptyClassKey = 'whenDateEmptyClass';

    public static array $databaseConstants = ['CURRENT_TIMESTAMP', 'CURRENT_TIME', 'CURRENT_DATE', 'NOW'];

    public string $dateFormat;

    public string $description;

    public int $size;
    public string $storageFormat;

    /**
     * @inheritDoc
     */
    public function apply(MetaModelInterface $metaModel, string $fieldName)
    {
        $metaModel->set($fieldName, $this->getSettings());

        // Create functions with passed on metaModels as we do not want to store it.
        $type = $this;
        $metaModel->set($fieldName, ['formatFunction' => function ($value) use ($type, $fieldName, $metaModel) {
            return $type->format($value, $fieldName, $metaModel);
        }]);
        $metaModel->setOnLoad($fieldName, function ($value, bool $isNew = false, string $name = null, array $context = [], bool $isPost = false) use ($type, $fieldName, $metaModel) {
            return $type->getDateTimeValue($value, $isNew, $fieldName, $context, $isPost, $metaModel);
        });
        $metaModel->setOnSave($fieldName, function ($value, bool $isNew = false, string $name = null, array $context = []) use ($type, $fieldName, $metaModel) {
            return $type->getStringValue($value, $isNew, $fieldName, $context, $metaModel);
        });
    }

    public function format($value, string $name, MetaModelInterface $metaModel)
    {
        if (! $value instanceof \DateTimeInterface) {
            $value = self::toDate(
                $value,
                $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                false);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format($metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat));
        }
        if (! $value) {
            if ($metaModel->has($name, self::$whenDateEmptyKey)) {
                $class = $metaModel->get($name, self::$whenDateEmptyClassKey);
                $empty = $metaModel->get($name, self::$whenDateEmptyKey);
                if ($class) {
                    Html::create('span', $empty, ['class' => $class]);
                }
                return $empty;
            }
        }

        return $value;
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
     * @param MetaModelInterface $metaModel
     * @return mixed The value to use instead
     */
    public function getDateTimeValue(mixed $value, bool $isNew, string $name, array $context, bool $isPost, MetaModelInterface $metaModel)
    {
        if ($name) {
            return $this->toDate(
                $value,
                $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                $isPost);
        }

        return $this->toDate($value, $this->storageFormat, $this->dateFormat, $isPost);
    }

    /**
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param MetaModelInterface $metaModel
     * @return string
     */
    public function getStringValue($value, $isNew, $name, array $context, MetaModelInterface $metaModel)
    {
        if ($name) {
            $this->toString(
                $value,
                $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                true
            );
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