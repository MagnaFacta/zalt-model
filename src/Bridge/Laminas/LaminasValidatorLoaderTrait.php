<?php

declare(strict_types=1);


/**
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge\Laminas;

use Laminas\Validator\ValidatorInterface;
use Zalt\Model\Exception\ModelValidatorLoadException;

/**
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @since      Class available since version 1.0
 */
trait LaminasValidatorLoaderTrait
{
    /**
     * Add validator to validation chain
     *
     * Note: will overwrite existing validators if they are of the same class.
     *
     * @param  string|ValidatorInterface $validator
     * @param  bool $breakChainOnFailure
     * @param  array $options
     * @return self
     * @throws \Zend_Form_Exception if invalid validator type
     */
    public function addValidator($validator, $breakChainOnFailure = false, $options = [])
    {
        if ($validator instanceof ValidatorInterface) {
            $name = get_class($validator);
        } elseif (is_string($validator)) {
            $name      = $validator;
            $validator = [
                'validator' => $validator,
                'breakChainOnFailure' => $breakChainOnFailure,
                'options'             => $options,
            ];
        } else {
            throw new ModelValidatorLoadException('Invalid validator provided to addValidator; must be string or \\Laminas\\Validator\\ValidatorInterface');
        }

        $this->_validators[$name] = $validator;

        return $this;
    }

    /**
     * Add multiple validators
     *
     * @param  array $validators
     * @return \Zend_Form_Element
     */
    public function addValidators(array $validators)
    {
        foreach ($validators as $validatorInfo) {
            if (is_string($validatorInfo)) {
                $this->addValidator($validatorInfo);
            } elseif ($validatorInfo instanceof ValidatorInterface) {
                $this->addValidator($validatorInfo);
            } elseif (is_array($validatorInfo)) {
                $argc                = count($validatorInfo);
                $breakChainOnFailure = false;
                $options             = [];
                if (isset($validatorInfo['validator'])) {
                    $validator = $validatorInfo['validator'];
                    if (isset($validatorInfo['breakChainOnFailure'])) {
                        $breakChainOnFailure = $validatorInfo['breakChainOnFailure'];
                    }
                    if (isset($validatorInfo['options'])) {
                        $options = $validatorInfo['options'];
                    }
                    $this->addValidator($validator, $breakChainOnFailure, $options);
                } else {
                    switch (true) {
                        case (0 == $argc):
                            break;
                        case (1 <= $argc):
                            $validator  = array_shift($validatorInfo);
                        case (2 <= $argc):
                            $breakChainOnFailure = array_shift($validatorInfo);
                        case (3 <= $argc):
                            $options = array_shift($validatorInfo);
                        default:
                            $this->addValidator($validator, $breakChainOnFailure, $options);
                            break;
                    }
                }
            } else {
                throw new ModelValidatorLoadException('Invalid validator passed to addValidators() ' . get_class($validatorInfo));
            }
        }

        return $this;
    }
}