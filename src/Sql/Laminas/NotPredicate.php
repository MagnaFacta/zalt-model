<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql\Laminas;

use Laminas\Db\Sql\Predicate\PredicateInterface;
use Laminas\Db\Sql\Predicate\PredicateSet;

/**
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @since      Class available since version 1.0
 */
class NotPredicate extends \Laminas\Db\Sql\Predicate\PredicateSet
{
    public const COMBINED_BY_NOT = 'NOT';

    public function __construct(?array $predicates = null)
    {
        parent::__construct($predicates, self::COMBINED_BY_AND);
    }

    public function getExpressionData()
    {
        $parts[] = 'NOT (';
        $parts = array_merge($parts, parent::getExpressionData());
        $parts[] = ')';
        return $parts;
    }
}