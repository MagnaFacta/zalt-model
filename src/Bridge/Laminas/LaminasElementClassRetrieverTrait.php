<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge\Laminas;

use Zalt\Model\Bridge\ValidatorBridgeInterface;

/**
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @since      Class available since version 1.0
 */
trait LaminasElementClassRetrieverTrait
{
    public function getElementClassFor(string $name): string
    {
        if ($this->metaModel->has($name, 'elementClass')) {
            return $this->metaModel->get($name, 'elementClass');
        }
        if ($this->metaModel->has($name, 'label')) {
            if ($this->metaModel->has('multiOptions')) {
                return 'Select';
            }
            return 'Text';
        }
        return 'Hidden';
    }
}