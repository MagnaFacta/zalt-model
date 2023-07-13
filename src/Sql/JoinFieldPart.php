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
class JoinFieldPart
{
    protected bool $expression = false;

    protected readonly string $fieldName;

    protected ?string $tableAliasName = null;

    protected ?string $tableName = null;

    public function __construct(string $fieldName)
    {
        $this->expression = (bool) strpbrk($fieldName, '(),`"\' ');

        if ($this->expression) {
            $this->fieldName = $fieldName;
        } else {
            $dot = strrpos($fieldName, '.');
            if (false === $dot) {
                $this->fieldName = $fieldName;
            } else {
                $this->fieldName = substr($fieldName, $dot + 1);
                $this->setTableName(substr($fieldName, 0, $dot));
            }
        }
    }

    public function __toString(): string
    {
        return $this->getJoinExpression();
    }

    public function getJoinExpression(): string
    {
        if ($this->expression) {
            return $this->fieldName;
        }
        if (isset($this->tableAliasName)) {
            return $this->tableAliasName . '.' . $this->fieldName;
        }
        return $this->tableName . '.' . $this->fieldName;
    }

    /**
     * @return string|null The name in the model
     */
    public function getNameInModel(): ?string
    {
        if ($this->expression) {
            return null;
        }

        if ($this->tableAliasName) {
            return $this->tableAliasName . '.' . $this->fieldName;
        }
        return $this->fieldName;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function hasTableName(): bool
    {
        return isset($this->tableName) && $this->tableName;
    }

    public function isExpression(): bool
    {
        return $this->expression;
    }

    /**
     * @param string|null $tableAliasName
    */
    public function setAliasName(?string $tableAliasName): void
    {
        if ($this->expression) {
            throw new ModelException("Cannot set an alias for a expression type join field.");
        }
        $this->tableAliasName = $tableAliasName;
    }

    public function setTableName(string $tableName): void
    {
        if ($this->expression) {
            throw new ModelException("Cannot set a table for a expression type join field.");
        }
        $this->tableName = $tableName;
    }
}