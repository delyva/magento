<?php
namespace Delyvax\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Psr\Log\LoggerInterface;

class OrderCancel implements ObserverInterface
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

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $serviceCode = $this->_delyvaxHelper->getServiceCodeFromShippingMethod($order->getShippingMethod());
        if ($serviceCode == 'delyvax_shipment') {
            return;
        }
        
        // check if order shipping method is delyvax
        if (strpos($order->getShippingMethod(), DelyvaxHelper::DELYVAX_SHIPMENT_CODE) !== false) {
            $delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
            if($delyvaxConfig['cancel_order_in_delvya']) {
                //Call API to cancel order in Delvyax
                $this->_delyvaxHelper->cancelDelyvaxOrder($order->getDelyvaxOrderId());
            }
        }
    }

}
