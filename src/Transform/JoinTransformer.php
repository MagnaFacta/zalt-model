<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Transform
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Transform;

use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;

/**
 *
 * @package    Zalt
 * @subpackage Model\Transform
 * @since      Class available since version 1.0
 */
class JoinTransformer extends SubmodelTransformerAbstract
{
    protected function transformLoadSubModel(MetaModelInterface $model, DataReaderInterface $sub, array &$data, array $join, $name, $new, $isPostData)
    {
        if (1 === count($join)) {
            // Suimple implementation
            $mkey = key($join);
            $skey = reset($join);

            $mfor = \MUtil\Ra::column($mkey, $data);

            // \MUtil\EchoOut\EchoOut::track($mfor);
            if ($new) {
                $sdata = $sub->loadNew(1);
            } else {
                $sdata = $sub->load(array($skey => $mfor));
            }
            // \MUtil\EchoOut\EchoOut::track($sdata);

            if ($sdata) {
                $skeys = array_flip(\MUtil\Ra::column($skey, $sdata));
                $empty = array_fill_keys(array_keys(reset($sdata)), null);

                foreach ($data as &$mrow) {
                    $mfind = $mrow[$mkey];

                    if (isset($skeys[$mfind])) {
                        $mrow += $sdata[$skeys[$mfind]];
                    } else {
                        $mrow += $empty;
                    }
                }
            } else {
                $empty = array_fill_keys($sub->getItemNames(), null);

                foreach ($data as &$mrow) {
                    $mrow += $empty;
                }
            }
            // \MUtil\EchoOut\EchoOut::track($mrow);
        } else {
            // Multi column implementation
            $empty = array_fill_keys($sub->getItemNames(), null);
            foreach ($data as &$mrow) {
                $filter = $sub->getFilter();
                foreach ($join as $from => $to) {
                    if (isset($mrow[$from])) {
                        $filter[$to] = $mrow[$from];
                    }
                }

                if ($new) {
                    $sdata = $sub->loadNew();
                } else {
                    $sdata = $sub->loadFirst($filter);
                }

                if ($sdata) {
                    $mrow += $sdata;
                } else {
                    $mrow += $empty;
                }

                // \MUtil\EchoOut\EchoOut::track($sdata, $mrow);
            }
        }
    }

    protected function transformSaveSubModel(MetaModelInterface $model, FullDataInterface $sub, array &$row, array $join, $name)
    {
        $keys = array();

        // Get the parent key values.
        foreach ($join as $parent => $child) {
            if (isset($row[$parent])) {
                $keys[$child] = $row[$parent];
            }
        }

        $row   = $keys + $row;
        $saved = $sub->save($row);

        $row = $saved + $row;
    }
}