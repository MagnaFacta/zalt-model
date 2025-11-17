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
class ActivatingYesNoType extends YesNoType
{
    public static string $activatingValue = 'activatingValue';
    public static string $deactivatingValue = 'deactivatingValue';

    public function getSettings(): array
    {
        $settings = parent::getSettings();

        $settings[self::$activatingValue] = array_key_first($this->labels);
        $settings[self::$deactivatingValue] = array_key_last($this->labels);

        return $settings;
    }

    /**
     * For multi value settings: returns the first array item as value to set.
     *
     * @param MetaModelInterface $metaModel
     * @return array [name => value]
     */
    public static function getActivatingValues(MetaModelInterface $metaModel): array
    {
        $output = [];
        foreach ($metaModel->getCol(self::$activatingValue) as $name => $value) {
            if (is_array($value)) {
                $output[$name] = reset($value);
            } else {
                $output[$name] = $value;
            }
        }
        return $output;
    }

    /**
     * For multi value settings: returns the first array item as value to set.
     *
     * @param MetaModelInterface $metaModel
     * @return array [name => value]
     */
    public static function getDeactivatingValues(MetaModelInterface $metaModel): array
    {
        $output = [];
        foreach ($metaModel->getCol(self::$deactivatingValue) as $name => $value) {
            if (is_array($value)) {
                $output[$name] = reset($value);
            } else {
                $output[$name] = $value;
            }
        }
        return $output;
    }

    /**
     * @param MetaModelInterface $metaModel
     * @return bool True if the metamodel has activation data stored
     */
    public static function hasActivation(MetaModelInterface $metaModel): bool
    {
        return ($metaModel->getCol(self::$activatingValue)  || $metaModel->getCol(self::$deactivatingValue));
    }

    /**
     * @param MetaModelInterface $metaModel
     * @param array $row
     * @return bool True if all values are the active values
     */
    public static function isActive(MetaModelInterface $metaModel, array $row): bool
    {
        foreach ($metaModel->getCol(self::$activatingValue) as $name => $value) {
            if (! isset($row[$name])) {
                return false;
            } elseif (is_array($value)) {
                if (! in_array($row[$name], $value)) {
                    return false;
                }
            } elseif ($value != $row[$name]) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param MetaModelInterface $metaModel
     * @param array $row
     * @return bool True if one of the values is not equal to the deactivating values (i.e. not: ! self::isActive())
     * /
    public static function isInactive(MetaModelInterface $metaModel, array $row): bool
    {
        foreach ($metaModel->getCol(self::$deactivatingValue) as $name => $value) {
            if (! isset($row[$name])) {
                return true;
            } elseif (is_array($value)) {
                if (in_array($row[$name], $value)) {
                    return true;
                }
            } elseif ($value != $row[$name]) {
                return true;
            }
        }
        return false;
    } // */
}