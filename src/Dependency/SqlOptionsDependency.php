<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Dependency;

use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Zalt
 * @subpackage Model\Dependency
 * @since      Class available since version 1.0
 */
class SqlOptionsDependency extends DependencyAbstract
{
    protected array $_defaultEffects = ['multiOptions' => 'multiOptions'];

    protected bool $required = false;

    /**
     * @param string $fieldName
     * @param string $tableName
     * @param string $valueColumn
     * @param string $labelColumn
     * @param string[] $links modelField => lookupField
     * @param TranslatorInterface $translate
     * @param SqlRunnerInterface $sqlRunner
     */
    public function __construct(
        protected readonly string $fieldName,
        protected readonly string $tableName,
        protected readonly string $valueColumn,
        protected readonly string $labelColumn,
        protected readonly array $links,
        protected readonly array $fixedFilter,
        protected readonly ?string $emptyOption,
        TranslatorInterface $translate,
        protected readonly SqlRunnerInterface $sqlRunner,
    )
    {
        foreach ($links as $field => $value) {
            if (! is_int($field)) {
                $this->_dependentOn[] = $field;
            }
        }
        $this->_effecteds = [$this->fieldName];

        parent::__construct($translate);
    }

    public function applyToModel(MetaModelInterface $metaModel)
    {
        parent::applyToModel($metaModel);

        $this->required = (bool) $metaModel->get($this->fieldName, 'required');

        $metaModel->set($this->fieldName, [
            'multiOptions' => $this->getOptions(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $filter = [];
        foreach ($this->links as $contextField => $lookupField) {
            if (isset($context[$contextField])) {
                $filter[$lookupField] = $context[$contextField];
            }
        }
        return [$this->fieldName => [
            'multiOptions' => $this->getOptions($filter),
            ]];
    }

    protected function getOptions(array $filter = []): array
    {
        $options = [];

        if ($this->emptyOption) {
            $options[''] = $this->emptyOption;
        }

        $result = $this->sqlRunner->fetchRows(
            $this->tableName,
            ['value' => $this->valueColumn, 'label' => $this->labelColumn],
            $filter + $this->fixedFilter,
            [$this->labelColumn => SORT_ASC]
        );
        // dump($filter + $this->fixedFilter, $result);

        foreach ($result as $row) {
            $options[$row['value']] = $row['label'];
        }

        return $options;
    }
}