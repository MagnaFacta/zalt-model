<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

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
}