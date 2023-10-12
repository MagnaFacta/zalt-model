<?php

namespace Zalt\Model\Type;

use Zalt\Html\ElementInterface;
use Zalt\Html\Html;
use Zalt\Html\HtmlInterface;
use Zalt\Html\Sequence;
use Zalt\Html\TableElement;
use Zalt\Model\MetaModel;
use Zalt\Model\MetaModelInterface;

class JsonType extends AbstractModelType
{
    /**
     *
     * @param int $maxTable Max number of rows to display in table display
     * @param string $separator Separator in table display
     * @param string $more There is more in table display
     */
    public function __construct(
        protected readonly int $maxTable = 3,
        protected readonly string $separator = '<br />',
        protected readonly string $more = '...'
    )
    {
    }

    /**
     * Use this function for a default application of this type to the model
     *
     * @param MetaModelInterface $metaModel
     * @param string $name The field to set the separator character
     */
    public function apply(MetaModelInterface $metaModel, string $name): void
    {
        $metaModel->set($name, 'formatFunction', 'format');
        $metaModel->setOnLoad($name, [$this, 'loadValue']);
        $metaModel->setOnSave($name, [$this, 'saveValue']);
    }

    public function applyTableView(MetaModelInterface $metaModel, string $name): void
    {
        $metaModel->set($name, 'formatFunction', 'formatTable');
    }

    /**
     * Displays the content
     *
     * @param mixed $value
     * @return string|ElementInterface
     */
    public function format(mixed $value): string|ElementInterface
    {
        if ((null === $value) || is_scalar($value)) {
            return $value ?? '';
        }
        if (! is_array($value)) {
            return TableElement::createArray($value)
                ->appendAttrib('class', 'jsonNestedObject');
        }
        foreach ($value as $key => $val) {
            if (! (is_int($key) && (is_scalar($val) || ($val instanceof HtmlInterface)))) {
                return TableElement::createArray($value)
                    ->appendAttrib('class', 'jsonNestedArray');
            }
        }
        return Html::create('ul', $value, ['class' => 'jsonArrayList']);
    }

    /**
     * Displays the content
     *
     * @param mixed $value
     * @return string|ElementInterface
     */
    public function formatTable(mixed $value): string|ElementInterface
    {
        if ((null === $value) || is_scalar($value)) {
            return $value;
        }
        if (is_array($value)) {
            $i = 0;
            $output = new Sequence();
            $output->setGlue($this->_separator);
            foreach ($value as $val) {
                if ($i++ > $this->_maxTable) {
                    $output->append($this->_more);
                    break;
                }
                $output->append($val);
            }
            return $output;
        }
        return TableElement::createArray($value);
    }

    /**
     * A ModelAbstract->setOnLoad() function that concatenates the
     * value if it is an array.
     *
     * @see MetaModelInterface
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return array Of the values
     */
    public function loadValue(mixed $value, bool $isNew = false, ?string $name = null, array $context = [], bool $isPost = false): ?array
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return null;
    }

    /**
     * A ModelAbstract->setOnSave() function that concatenates the
     * value if it is an array.
     *
     * @see MetaModelInterface
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string Of the values concatenated
     */
    public function saveValue(mixed $value, bool $isNew = false, ?string $name = null, array $context = []): ?string
    {
        if ($value === null) {
            return null;
        }
        return json_encode($value);
    }

    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_STRING;
    }

    public function getSettings(): array
    {
        $output['formatFunction'] = [$this, 'format'];
        $output[MetaModel::LOAD_TRANSFORMER] = [$this, 'loadValue'];
        $output[MetaModel::SAVE_TRANSFORMER] = [$this, 'saveValue'];

        return $output;
    }
}