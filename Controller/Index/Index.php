<?php

namespace Delyvax\Shipment\Controller\Index;

use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Model\Order\Shipment\TrackFactory;

class Index extends \Magento\Framework\App\Action\Action
{

    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var ConvertOrder
     */
    private $_convertOrder;

    /**
     * @var ShipmentNotifier
     */
    private $_shipmentNotifier;

    /**
     * @var TrackFactory
     */
    private $_trackFactory;

    public function __construct(
        DelyvaxHelper $delyvaxHelper,
        ObjectManagerInterface $objectmanager,
        ConvertOrder $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        TrackFactory $trackFactory,
        Context $context
    )
    {
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->_objectManager = $objectmanager;
        $this->_convertOrder = $convertOrder;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_trackFactory = $trackFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')
            ->loadByAttribute('increment_id', '000000135');
        $order->addCommentToStatusHistory('Shipment is ready to collect by ' . $order->getShippingDescription(), 'dx-preparing', true);
        $order->save();
        /*// Load the order
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')
            ->loadByAttribute('increment_id', '000000135');

        // Check if order has already shipped or can be shipped
        if (!$order->canShip()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You can\'t create an shipment.')
            );
        }

        // Initialize the order shipment object
        // $convertOrder = $this->_objectManager->create('Magento\Sales\Model\Convert\Order');
        $shipment = $this->_convertOrder->toShipment($order);

        // Loop through order items
        foreach ($order->getAllItems() as $orderItem) {
            // Check if order item is virtual or has quantity to ship
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }
            $qtyShipped = $orderItem->getQtyToShip();

            // Create shipment item with qty
            // $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
            $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

            // Add shipment item to shipment
            $shipment->addItem($shipmentItem);
        }

        // Register shipment
        $shipment->register();

        $data = array(
            'carrier_code' => 'ups',
            'title' => 'United Parcel Service',
            'number' => 'TORD23254WE4434RZXd3', // Replace with your tracking number
        );

        $shipment->getOrder()->setIsInProcess(true);

        try {
            // Save created shipment and order
            $track = $this->_trackFactory->create()->addData($data);
            $shipment->addTrack($track)->save();
            $shipment->save();
            $shipment->getOrder()->save();

            // Send email
            $this->_shipmentNotifier->notify($shipment);
            $shipment->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }*/

        /*var_dump($this->_delyvaxHelper->getOrderOriginScheduledAt());
        var_dump($this->_delyvaxHelper->getOrderDestinationScheduledAt());
        var_dump($this->_delyvaxHelper->getServiceCodeFromShippingMethod('delyvax_shipment_NDD-TELEPORT'));
        echo "<pre>"; var_dump($this->_delyvaxHelper->getOrderOriginContact()); echo "</pre>";*/

        /*$delyvaxOrderId = 'b7c68d33-ddfe-4bbb-bb75-61524565fbdc';
        $order = $this->_delyvaxHelper->loadOrderByDelyvaxOrderId($delyvaxOrderId);
        var_dump($order->getIncrementId());
        var_dump($order->getDelyvaxConsignmentNumber());
        var_dump($order->getDelyvaxOrderStatus());
        var_dump($order->getStatus());
        die();*/

        /*$res = $this->splitName('Josh Moore');
        var_dump($res);
        echo "<pre>"; var_dump($res); echo "</pre>";*/

        /*$delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
        echo "<pre>"; var_dump($delyvaxConfig); echo "</pre>";
        if ($delyvaxConfig['create_shipment_on_paid']) {
            echo 'true';
        }else{
            echo 'false';
        }*/

        /* file_put_contents('var/log/orderPlaceAfter.txt', '\n--------\CurrencyCode: \n'.print_r($order->getOrderCurrencyCode(), TRUE), FILE_APPEND); die();
        file_put_contents('var/log/orderPlaceBefore.txt', '\n--------------------------\Billing Address: \n'.print_r($order->getBillingAddress()->getData(), TRUE), FILE_APPEND);
        file_put_contents('var/log/orderPlaceBefore.txt', '\n--------------------------\Shipping Address: \n'.print_r($order->getShippingAddress()->getData(), TRUE), FILE_APPEND);
        file_put_contents('var/log/orderPlaceAfter.txt', '\n--------------------------\getShippingMethod: \n'.print_r($order->getShippingMethod(), TRUE), FILE_APPEND); // delyvax_shipment_TAXI-KV
        file_put_contents('var/log/orderPlaceAfter.txt', '\n--------------------------\getShippingDescription: \n'.print_r($order->getShippingDescription(), TRUE), FILE_APPEND); // DelyvaX Shipment - TAXI KV
        file_put_contents('var/log/orderPlaceBefore.txt', '\n--------------------------\Payment Method: \n'.print_r($order->getPayment()->getMethodInstance()->getTitle(), TRUE), FILE_APPEND); */


    }

}
