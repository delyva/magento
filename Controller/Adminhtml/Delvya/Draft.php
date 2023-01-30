<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Delyvax\Shipment\Controller\Adminhtml\Delvya;

use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Draft extends \Magento\Backend\App\Action
{
    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderFactory;

    /**
     * @var ResourceOrder
     */
    private $_resourceOrder;

     /**
     * @var QuoteRepository
     */
    protected $_quoteRepository;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        DelyvaxHelper $delyvaxHelper,
        LoggerInterface $logger,
        OrderFactory $orderFactory,
        QuoteRepository $quoteRepository,
        ResourceOrder $resourceOrder
    ) {
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->_quoteRepository = $quoteRepository;
        $this->_resourceOrder = $resourceOrder;
        parent::__construct($context);
    }
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderFactory->create()->load($orderId);
        
        if (strpos($order->getShippingMethod(), DelyvaxHelper::DELYVAX_SHIPMENT_CODE) === false) {
            try {
                $quote = $this->_quoteRepository->get($order->getQuoteId());
            } catch (NoSuchEntityException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    $e->getMessage()
                );
            }

            $originScheduledAt = $this->_delyvaxHelper->getOrderOriginScheduledAt();
            // Get Inventories for Create Order Request
            $inventories = $this->_delyvaxHelper->getInventoriesToCreateOrderByQuote($quote, $order->getOrderCurrencyCode());
            $OriginOrderNotes = 'Order No: ' . $order->getIncrementId() . '<br/>Date: '. $originScheduledAt .' (24H) <br>';
            $originContact = $this->_delyvaxHelper->getOrderOriginContact();

            $origin = [
                "scheduledAt" => $originScheduledAt,
                "inventory" => $inventories,
                "contact" => $originContact,
                "note" => $OriginOrderNotes
            ];

            $destinationScheduledAt = $originScheduledAt; // $this->_delyvaxHelper->getOrderDestinationScheduledAt();
            $destinationContact = $this->_delyvaxHelper->getDestinationContact($order);
            $destinationOrderNotes = 'Order No: ' . $order->getIncrementId() . '<br/>Date: '. $destinationScheduledAt .' (24H) <br>';
            $destination = [
                "scheduledAt" => $destinationScheduledAt,
                "inventory" => $inventories,
                "contact" => $destinationContact,
                "note" => $destinationOrderNotes
            ];

            $serviceCode = '';
            // check payment method and set codAmount
            $codAmount = ($order->getPayment()->getMethod() === \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) ? $order->getGrandTotal() : 0;
            $cod = [
                "amount" => (string) $codAmount,
                "currency" => $order->getOrderCurrencyCode()
            ];
            $process = false;
            $createOrderResponse = $this->_delyvaxHelper->postCreateOrder($origin, $destination, $serviceCode, $cod, $OriginOrderNotes, $process, $order->getIncrementId());

            if ($createOrderResponse[DelyvaxHelper::STATUS]) {
                $createOrderResponse = $createOrderResponse[DelyvaxHelper::RESPONSE];
                $orderRespData['delyvax_origin_scheduled_at'] = strtotime($originScheduledAt);
                $orderRespData['delyvax_dest_scheduled_at'] = strtotime($destinationScheduledAt);
                if (array_key_exists('data', $createOrderResponse)) {
                    $orderRespData['delyvax_order_id'] = (array_key_exists('id', $createOrderResponse['data'])) ? $createOrderResponse['data']['id'] : NULL;
                    $orderRespData['delyvax_consignment_number'] = (array_key_exists('consignmentNo', $createOrderResponse['data'])) ? $createOrderResponse['data']['consignmentNo'] : NULL;
                    $orderRespData['delyvax_order_status'] = (array_key_exists('status', $createOrderResponse['data'])) ? $createOrderResponse['data']['status'] : NULL;
                }

                try {
                    $order->addData($orderRespData);
                    $this->_resourceOrder->save($order);
                    $this->messageManager->addSuccess(__('You created the shipping draft with delvya.'));
                } catch (LocalizedException | \Exception $exception) {
                    $this->logger->critical($exception->getMessage());
                }
            }
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
