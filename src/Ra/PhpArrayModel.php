<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Ra;

use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @since      Class available since version 1.0
 */
class PhpArrayModel extends ArrayModelAbstract implements FullDataInterface
{
    protected array $data;

    public function __construct(
        MetaModelInterface $metaModel,
        \ArrayObject $dataObject,
    )
    {
        parent::__construct($metaModel);
        $this->data = $dataObject->getArrayCopy();
    }

    /**
     * @inheritDoc
     */
    protected function _loadAll(): array
    {
        return $this->data;
    }

    /**
     * When $this->_saveable is true a child class should either override the
     * delete() and save() functions of this class or override _saveAllTraversable().
     *
     * In the latter case this class will use _loadAllTraversable() and remove / add the
     * data to the data in the delete() / save() functions and pass that data on to this
     * function.
     *
     * @param array $data An array containing all the data that should be in this object
     * @return void
     */
    protected function _saveAll(array $data)
    {
        $this->data = $data;
    }
}