<?php

namespace Zalt\Model\Ra;

trait SortArrayTrait
{
    protected function sortData(array $data, array $sorts): array
    {
        $usedSorts = [];

        foreach ($sorts as $key => $order) {
            if (is_numeric($key) || is_string($order)) {
                if (strtoupper(substr($order,  -5)) === ' DESC') {
                    $order     = substr($order,  0,  -5);
                    $direction = SORT_DESC;
                } else {
                    if (strtoupper(substr($order,  -4)) === ' ASC') {
                        $order = substr($order,  0,  -4);
                    }
                    $direction = SORT_ASC;
                }
                $usedSorts[$order] = $direction;
                continue;

            }
            if ($order !== SORT_DESC) {
                $order = SORT_ASC;
            }
            $usedSorts[$key] = $order;
        }

        usort($data, function(array $a, array $b) use ($usedSorts) {
            foreach($usedSorts as $order => $direction) {
                if ($a[$order] !== $b[$order]) {
                    if (SORT_ASC === $direction) {
                        return $a[$order] > $b[$order] ? 1 : -1;
                    }
                    return $a[$order] > $b[$order] ? -1 : 1;
                }
            }
            return 0;
        });

        return $data;
    }
}