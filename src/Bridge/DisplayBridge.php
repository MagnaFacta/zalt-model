<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge;

use DateTimeImmutable;
use DateTimeInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Bridge
 * @since      Class available since version 1.0
 */
class DisplayBridge extends BridgeAbstract
{
    /**
     * Return an array of functions used to process the value
     *
     * @param string $name The real name and not e.g. the key id
     * @return array
     */
    protected function _compile($name): array
    {
        $output = [];
        
        if ($this->metaModel->has($name, 'multiOptions')) {
            $options = $this->metaModel->get($name, 'multiOptions');

            $output['multiOptions'] = function ($value) use ($options) {
                if (null === $value) {
                    return isset($options['']) ? $options[''] : null;
                }
                return is_scalar($value) && array_key_exists($value, $options) ? $options[$value] : $value;
            };
        }

        if ($this->metaModel->has($name, 'formatFunction')) {
            $output['formatFunction'] = $this->metaModel->get($name, 'formatFunction');

        } elseif ($this->metaModel->has($name, 'dateFormat')) {
            $format = $this->metaModel->get($name, 'dateFormat');
            if (is_callable($format)) {
                $output['dateFormat'] = $format;
            } else {
                $storageFormat = $this->metaModel->get($name, 'storageFormat');
                $output['dateFormat'] = function ($value) use ($format, $storageFormat) {
                    if ($value === null) {
                        return null;
                    }
                    if ($value instanceof DateTimeInterface) {
                        $date = $value;
                    } else {
                        $date = DateTimeImmutable::createFromFormat($storageFormat, $value);
                    }
                    if ($date) {
                        return $date->format($format);
                    } else {
                        return null;
                    }
                };
            }
        } elseif ($this->metaModel->has($name, 'numberFormat')) {
            $format = $this->metaModel->get($name, 'numberFormat');
            if (is_callable($format)) {
                $output['numberFormat'] = $format;
            } else {
                $output['numberFormat'] = function ($value) use ($format) {
                    // return \Zend_Locale_Format::toNumber($value, array('number_format' => $format));
                    // TODO: how are we going to format numbers from now on?
                    $locale = localeconv();
                    return number_format($value,2,
                                         $locale['decimal_point'],
                                         $locale['thousands_sep']);
                    
                };
            }
        }

        if ($this->metaModel->has($name, 'markCallback')) {
            $output['markCallback'] = $this->metaModel->get($name, 'markCallback');
        }

        return $output;
    }
}