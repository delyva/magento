<?php

namespace Delyvax\Shipment\Controller\Index;

use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    public function __construct(
        DelyvaxHelper $delyvaxHelper,
        Context $context
    )
    {
        $this->_delyvaxHelper = $delyvaxHelper;
        parent::__construct($context);
    }

    public function execute()
    {
//        var_dump($this->_delyvaxHelper->getOrderOriginScheduledAt());
//        var_dump($this->_delyvaxHelper->getOrderDestinationScheduledAt());
//        var_dump($this->_delyvaxHelper->getServiceCodeFromShippingMethod('delyvax_shipment_NDD-TELEPORT'));
//        echo "<pre>"; var_dump($this->_delyvaxHelper->getOrderOriginContact()); echo "</pre>";


//        $res = $this->splitName('Josh Moore');
//        var_dump($res);
//        echo "<pre>"; var_dump($res); echo "</pre>";
        $delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
        echo "<pre>"; var_dump($delyvaxConfig); echo "</pre>";
        if ($delyvaxConfig['create_shipment_on_paid']) {
            echo 'true';
        }else{
            echo 'false';
        }
        /* file_put_contents('var/log/orderPlaceAfter.txt', '\n--------\CurrencyCode: \n'.print_r($order->getOrderCurrencyCode(), TRUE), FILE_APPEND); die();
        file_put_contents('var/log/orderPlaceBefore.txt', '\n--------------------------\Billing Address: \n'.print_r($order->getBillingAddress()->getData(), TRUE), FILE_APPEND);
        file_put_contents('var/log/orderPlaceBefore.txt', '\n--------------------------\Shipping Address: \n'.print_r($order->getShippingAddress()->getData(), TRUE), FILE_APPEND);
        file_put_contents('var/log/orderPlaceAfter.txt', '\n--------------------------\getShippingMethod: \n'.print_r($order->getShippingMethod(), TRUE), FILE_APPEND); // delyvax_shipment_TAXI-KV
        file_put_contents('var/log/orderPlaceAfter.txt', '\n--------------------------\getShippingDescription: \n'.print_r($order->getShippingDescription(), TRUE), FILE_APPEND); // DelyvaX Shipment - TAXI KV
        file_put_contents('var/log/orderPlaceBefore.txt', '\n--------------------------\Payment Method: \n'.print_r($order->getPayment()->getMethodInstance()->getTitle(), TRUE), FILE_APPEND); */


    }

}
