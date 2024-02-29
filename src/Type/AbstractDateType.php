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
    public function apply(MetaModelInterface $metaModel, string $name): void
    {
        $metaModel->set($name, $this->getSettings());

        if ($metaModel->has($name, 'label')) {
            $metaModel->set($name, ['elementClass' => 'Date']);
        }

        // Create functions with passed on metaModels as we do not want to store it.
        $type = $this;
        $metaModel->set($name, ['formatFunction' => function ($value) use ($type, $name, $metaModel) {
            return $type->format($value, $name, $metaModel);
        }]);
        $metaModel->setOnLoad($name, function ($value, bool $isNew = false, string $fieldName = null, array $context = [], bool $isPost = false) use ($type, $name, $metaModel) {
            return $type->getDateTimeValue($value, $isNew, $name, $context, $isPost, $metaModel);
        });
        $metaModel->setOnSave($name, function ($value, bool $isNew = false, string $fieldName = null, array $context = []) use ($type, $name, $metaModel) {
            return $type->getStringValue($value, $isNew, $name, $context, $metaModel);
        });
    }

    public function checkValue(mixed $value)
    {
        if (is_object($value) && method_exists($value, '__toString')) {
            return $value;
        }
        return $value;
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
            return $this->getNullDisplayValue($name, $metaModel);
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

    protected function getNullDisplayValue(string $name, MetaModelInterface $metaModel): mixed
    {
        if ($metaModel->has($name, self::$whenDateEmptyKey)) {
            $class = $metaModel->get($name, self::$whenDateEmptyClassKey);
            $empty = $metaModel->get($name, self::$whenDateEmptyKey);
            if ($class) {
                return Html::create('span', $empty, ['class' => $class]);
            }
            return $empty;
        }
        return null;
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

        return $this->getExtraSettings() + $output;
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
            return self::toDate(
                $this->checkValue($value),
                $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                $isPost);
        }

        return self::toDate($value, $this->storageFormat, $this->dateFormat, $isPost);
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
            return self::toString(
                $this->checkValue($value),
                $metaModel->getWithDefault($name, 'storageFormat', $this->storageFormat),
                $metaModel->getWithDefault($name, 'dateFormat', $this->dateFormat),
                true
            );
        }

        return self::toString($value, $this->storageFormat, $this->dateFormat);
    }

    public static function toDate($value, string $storageFormat, string $dateFormat, bool $isPost = true): mixed
    {
        if ((null === $value) || ($value instanceof \DateTimeImmutable)) {
            return $value;
        }
        if ('' == $value) {
            return null;
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
        if ((null === $value) || (is_string($value) && ('' == $value))) {
            return null;
        }

        if (is_string($value) && in_array(strtoupper(rtrim($value, '()')), self::$databaseConstants)) {
            return date($storageFormat);
        }

        if (! $value instanceof \DateTimeInterface) {
            $value = self::toDate($value, $storageFormat, $dateFormat, $isPost);
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format($storageFormat);
        }

        return (string) $value;
    }
}