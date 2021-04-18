<?php
namespace Delyvax\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Psr\Log\LoggerInterface;

class OrderPaymentPay implements ObserverInterface
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
        $delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
        if($delyvaxConfig['create_shipment_on_paid']) {
            $order = $observer->getEvent()->getInvoice()->getOrder();
            $this->_delyvaxHelper->processDelyvaxOrderIfDraft($order, true);
        }
    }

}
