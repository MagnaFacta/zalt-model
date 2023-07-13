<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

use Zalt\Model\Exception\ModelException;

/**
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
class JoinCondition
{
    protected JoinFieldPart $leftField;
    protected JoinFieldPart $rightField;

    public function getCondition(): string
    {
        if (isset($this->leftField) && isset($this->rightField)) {
            return $this->leftField->getJoinExpression() . ' = ' . $this->rightField->getJoinExpression();
        }
        if (isset($this->leftField)) {
            return $this->leftField->getJoinExpression();
        }
        if (isset($this->rightField)) {
            return $this->rightField->getJoinExpression();
        }
        throw new ModelException("No join set for condition!");
    }

    /**
     * @param string $leftField
     */
    public function setLeftField(string $leftField): JoinFieldPart
    {
        $this->leftField = new JoinFieldPart($leftField);

        return $this->leftField;
    }

    /**
     * @param string $rightField
     */
    public function setRightField(string|JoinFieldPart $rightField): JoinFieldPart
    {
        if ($rightField instanceof JoinFieldPart) {
            $this->rightField = $rightField;
        } else {
            $this->rightField = new JoinFieldPart($rightField);
        }

        return $this->rightField;
    }
}