<?php
namespace Delyvax\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Psr\Log\LoggerInterface;

class OrderShipmentSave implements ObserverInterface
{
    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        DelyvaxHelper $delyvaxHelper,
        LoggerInterface $logger
    ) {
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getShipment()->getOrder();
        $this->_delyvaxHelper->processDelyvaxOrderIfDraft($order);
    }

}
