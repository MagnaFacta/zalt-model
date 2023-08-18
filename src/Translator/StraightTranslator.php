<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Translator;

/**
 * @package    Zalt
 * @subpackage Model\Translator
 * @since      Class available since version 1.0
 */
class StraightTranslator extends ModelTranslatorAbstract
{
    protected string $description = 'Straight import';

    public function getFieldsTranslations(): array
    {
        $fieldList = [];

        $metaModel = $this->targetModel->getMetaModel();

        foreach ($metaModel->getCol('label') as $name => $label) {
            if (! ($metaModel->has($name, 'column_expression') ||
                $metaModel->is($name, 'elementClass', 'Exhibitor'))) {

                $fieldList[$name] = $name;
            }
        }

        return $fieldList;
    }
}