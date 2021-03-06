<?php

namespace Pimgento\Api\Observer\Deletion;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Pimgento\Api\Helper\Import\Entities;
use Pimgento\Api\Job\Family as ImportJob;

/**
 * Class FamilyObserver
 *
 * @category  Class
 * @package   Pimgento\Api\Observer\Deletion
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.pimgento.com/
 */
class FamilyObserver implements ObserverInterface
{
    /**
     * This variable contains an Entities
     *
     * @var Entities $entities
     */
    protected $entities;
    /**
     * This variable contains an Attribute
     *
     * @var ImportJob $job
     */
    protected $job;

    /**
     * FamilyObserver Constructor
     *
     * @param Entities $entities
     * @param ImportJob $job
     */
    public function __construct(
        Entities $entities,
        ImportJob $job
    ) {
        $this->entities = $entities;
        $this->job      = $job;
    }
    /**
     * Remove entity relation
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var $attributeSet \Magento\Eav\Model\Entity\Attribute\set */
        $attributeSet = $observer->getEvent()->getObject();

        $this->entities->delete($this->job->getCode(), $attributeSet->getId());
    }
}
