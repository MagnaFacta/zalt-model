<?php

declare(strict_types=1);

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Sql;

/**
 *
 * @package    Zalt
 * @subpackage Model\Sql
 * @since      Class available since version 1.0
 */
trait SqlModelTrait
{
    /**
     * A standard rename scaffold for hidden kopies of primary key fields.
     *
     * As this is the name a hidden \Zend_Element we can use only letters and the underscore for
     * the first character and letters, the underscore and numbers for the later characters.
     *
     * \Zend_Element allows some other extended characters, but those may not work
     * with some browsers.
     *
     * @var string $keyKopier String into which the original keyname is sprintf()-ed.
     */
    protected $keyCopier = '__c_1_3_copy__%s__key_k_0_p_1__';

    /**
     * Adds a column to the model
     *
     * @param string $column
     * @param ?string $columnName
     * @param ?string $orignalColumn
     * @return \Zalt\Model\Sql\SqlTableModel Provides a fluent interface
     */
    public function addColumn(string $column, string $columnName = null, string $orignalColumn = null)
    {
        if (null === $columnName) {
            $columnName = strtr((string) $column, ' .,;:?!\'"()<=>-*+\\/&%^', '______________________');
        }
        if ($orignalColumn) {
            $settings = $this->metaModel->setAlias($columnName, $orignalColumn);
        }
        $this->metaModel->set($columnName, 'column_expression', $column);

        return $this;
    }

    /**
     * Makes a copy for each key item in the model using $this->getKeyCopyName()
     * to create the new name.
     *
     * Call this function whenever the user is able to edit a key and the key is not
     * stored elsewhere (e.g. in a parameter). The save function using this value to
     * perform an update instead of an insert on a changed key.
     *
     * @param boolean $reset True if the key list should be rebuilt.
     * return \MUtil\Model\DatabaseModelAbstract $this
     */
    public function copyKeys($reset = false)
    {
        foreach ($this->metaModel->getKeys($reset) as $name) {
            $this->addColumn($name, $this->getKeyCopyName($name));
        }
        return $this;
    }

    /**
     * Returns the key copy name for a field.
     *
     * @param string $name
     * @return string
     */
    public function getKeyCopyName($name)
    {
        return sprintf($this->keyCopier, $name);
    }


}