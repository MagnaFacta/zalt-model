<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @author     Roel van Meer <roel.van.meer@peercode.nl>
 */

namespace Zalt\Model\Sql\Laminas;

use Laminas\Db\Adapter\Adapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql\Laminas
 * @since      Class available since version 2.0
 */
class CachedLaminasRunner extends LaminasRunner implements \Zalt\Model\Sql\SqlRunnerInterface
{
    public const TAG = 'schema';

    public function __construct(
        Adapter $db,
        protected AdapterInterface $cache,
    ) {
        parent::__construct($db);
    }

    private function getCacheKey(string $key): string
    {
        return 'table_schema_' . $key;
    }

    /**
     * @inheritDoc
     */
    public function getTableMetaData(string $tableName): array
    {
        $key = $this->getCacheKey($tableName);
        if ($this->cache->hasItem($key)) {
            return $this->cache->getItem($key)->get();
        }
        $data = parent::getTableMetaData($tableName);
        $item = $this->cache->getItem($key)->tag(self::TAG)->set($data);
        $this->cache->save($item);

        return $data;
    }
}
