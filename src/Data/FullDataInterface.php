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
interface FullDataInterface extends DataReaderInterface, DataWriterInterface
{
    /**
     * Delete items from the model
     *
     * @param mixed $filter Null to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = null): int;

    /**
     * Processes and returns an array of post data
     *
     * @param array $postData
     * @param boolean $create
     * @param mixed $filter Null to use the stored filter, array to specify a different filter
     * @param mixed $sort Null to use the stored sort, array to specify a different sort
     * @return array
     */
    public function loadPostData(array $postData, $create = false, $filter = null, $sort = null): array;
}