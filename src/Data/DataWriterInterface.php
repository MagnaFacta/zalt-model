<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Data
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Data;

/**
 *
 * @package    Zalt
 * @subpackage Model\Data
 * @since      Class available since version 1.0
 */
interface DataWriterInterface extends \Zalt\Model\MetaModellerInterface
{
    /**
     * The number of item rows changed since the last save or delete
     *
     * @return int
     */
    public function getChanged(): int;

    /**
     * True if this model allows the creation of new model items.
     *
     * @return boolean
     */
    public function hasNew(): bool;

    /**
     * Save a single model item.
     *
     * @param array $newValues The values to store for a single model item.
     * @param array $filter If the filter contains old key values these are used
     * to decide on update versus insert.
     * @return array The values as they are after saving (they may change).
     */
    public function save(array $newValues, array $filter = null): array;
}