<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Type;

use Zalt\Model\Dependency\SqlOptionsDependency;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Type
 * @since      Class available since version 1.0
 */
class SqlOptionsType extends AbstractUntypedType
{
    public const EMPTY_OPTION = 'emptyOption';

    public function __construct(
        protected readonly string $tableName,
        protected readonly string $valueColumn,
        protected ?string $labelColumn = null,
        protected readonly array $links = [],
        protected ?string $emptyOption = null,
        protected readonly array $fixedFilter = [],
    )
    {
        if (null === $this->labelColumn) {
            $this->labelColumn = $this->valueColumn;
        }
    }

    public function apply(MetaModelInterface $metaModel, string $name)
    {
        parent::apply($metaModel, $name);

        if (null === $this->emptyOption) {
            $this->emptyOption = $metaModel->getWithDefault($name, self::EMPTY_OPTION, null);
        }

        $metaModel->addDependency([SqlOptionsDependency::class, $name, $this->tableName, $this->valueColumn, $this->labelColumn, $this->links, $this->fixedFilter, $this->emptyOption]);
    }

    /**
     * @inheritDoc
     */
    public function getSettings(): array
    {
        return [
            'elementClass' => 'Select',
        ];
    }
}