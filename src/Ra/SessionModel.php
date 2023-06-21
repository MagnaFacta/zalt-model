<?php

declare(strict_types=1);

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Zalt\Model\Ra;

use Mezzio\Session\SessionInterface;
use Zalt\Model\MetaModelInterface;

/**
 * @package    Zalt
 * @subpackage Model\Ra
 * @since      Class available since version 1.0
 */
class SessionModel extends ArrayModelAbstract implements \Zalt\Model\Data\FullDataInterface
{
    protected string $_sessionId;

    public function __construct(
        MetaModelInterface $metaModel,
        protected SessionInterface $session,
        )
    {
        parent::__construct($metaModel);

        $this->_sessionId = get_class($this) . '_' . $metaModel->getName() . '_data';
    }

    /**
     * @inheritDoc
     */
    protected function _loadAll(): array
    {
        $this->session->get($this->_sessionId, []);
    }

    /**
     * @inheritDoc
     */
    protected function _saveAll(array $data)
    {
        $this->session->set($this->_sessionId, $data);
    }
}