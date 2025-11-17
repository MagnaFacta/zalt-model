<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Bridge\Laminas;

use Laminas\Filter\FilterInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\Bridge\FilterBridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Model\Exception\MetaModelException;
use Zalt\Model\ModelAwareInterface;
use Zalt\Model\ModelFieldNameAwareInterface;

/**
 * @package    Zalt
 * @subpackage Model\Bridge\Laminas
 * @since      Class available since version 1.0
 */
class LaminasFilterBridge extends \Zalt\Model\Bridge\BridgeAbstract implements FilterBridgeInterface
{
    use LaminasElementClassRetrieverTrait;

    /**
     * @var array elementClassName => compile function
     */
    protected array $_elementClassCompilers = [];

    /**
     * @var array typeIdentifyer => compile function
     */
    protected array $_typeClassCompilers = [];

    /**
     * @var array name => array of validators
     */
    protected array $_loadedFilters = [];

    protected ProjectOverloader $filterOverloader;

    public function __construct(DataReaderInterface $dataModel, ProjectOverloader $projectOverloader = null)
    {
        parent::__construct($dataModel);

        if (! $this->dataModel instanceof FullDataInterface) {
            throw new MetaModelException("Only FullDataInterface objects are allowed as input for a " . __CLASS__ . " constructor");
        }

        if (! $projectOverloader instanceof ProjectOverloader) {
            throw new MetaModelException("A ProjectOverloader objects is required as input for a " . __CLASS__ . " constructor");
        }

        $this->filterOverloader = $projectOverloader->createSubFolderOverloader('Filter');
        $this->filterOverloader->legacyClasses = false;

        $this->loadDefaultElementCompilers();
        $this->loadDefaultTypeCompilers();
    }

    /**
     * @inheritDoc
     */
    protected function _compile(string $name): array
    {
        return $this->getFiltersFor($name);
    }

    /**
     * Retrieve all filters for an field
     *
     * @param string $name
     * @return array validator name => validator or array for loading
     */
    public function gatherFiltersFor(string $name): array
    {
        $filters = $this->metaModel->get($name, 'filters') ?? [];

        if ($filter = $this->metaModel->get($name, 'filter')) {
            if ($filters) {
                array_unshift($filters, $filter);
            } else {
                $filters = array($filter);
            }
        }

        if (! $this->metaModel->get($name, 'ignoreElementFilters')) {
            $elementClass = $this->getElementClassFor($name);
            if (isset($this->_elementClassCompilers[$elementClass])) {
                $filters += call_user_func($this->_elementClassCompilers[$elementClass], $this->metaModel, $name);
            }
        }
        if (! $this->metaModel->get($name, 'ignoreTypeFilters')) {
            $typeId = $this->metaModel->get($name, 'type');
            if ($typeId && isset($this->_typeClassCompilers[$typeId])) {
                $filters += call_user_func($this->_typeClassCompilers[$typeId], $this->metaModel, $name);
            }
        }

        return $filters;
    }

    /**
     * @inheritDoc
     */
    public function getFiltersFor(string $name): array
    {
        if (isset($this->_loadedFilters[$name])) {
            return $this->_loadedFilters[$name];
        }

        $filters = $this->gatherFiltersFor($name);

        $this->_loadedFilters[$name] = $this->loadFilters($name, $filters);

        return $this->_loadedFilters[$name];
    }

    protected function loadDefaultElementCompilers()
    {
        // $this->setElementClassCompiler('File', [$this, 'getElementValidatorsFile'])
    }

    protected function loadDefaultTypeCompilers()
    { }

    /**
     * @param string $name
     * @param array $filters
     * @return FilterInterface[]
     */
    protected function loadFilters(string $name, array $filters): array
    {
        $output = [];

        foreach ($filters as $key => $filter) {
            if ($filter instanceof FilterInterface) {
                $output[$key] = $filter;
            } elseif (is_array($filter)) {
                $class = array_shift($filter);
                $output[$key] = $this->filterOverloader->create($class, $filter);
            } else {
                $output[$key] = $this->filterOverloader->create($filter);
            }

            if ($output[$key] instanceof ModelFieldNameAwareInterface) {
                $output[$key]->setName($name);
            }
            if ($output[$key] instanceof ModelAwareInterface) {
                $output[$key]->setDataModel($this->dataModel);
            }
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function setElementClassCompiler(string $elementClassName, callable $callable): FilterBridgeInterface
    {
        $this->_elementClassCompilers[$elementClassName] = $callable;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setTypeClassCompiler(int $typeId, callable $callable): FilterBridgeInterface
    {
        $this->_typeClassCompilers[$typeId] = $callable;
        return $this;
    }
}