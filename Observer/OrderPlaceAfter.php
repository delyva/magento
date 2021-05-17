<?php
namespace Delyvax\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;

class OrderPlaceAfter implements ObserverInterface
{
    /**
     * @var QuoteRepository
     */
    protected $_quoteRepository;
    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var ResourceOrder
     */
    private $_resourceOrder;

    public function __construct(
        QuoteRepository $quoteRepository,
        DelyvaxHelper $delyvaxHelper,
        LoggerInterface $logger,
        ResourceOrder $resourceOrder
    ) {
        $this->_quoteRepository = $quoteRepository;
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->logger = $logger;
        $this->_resourceOrder = $resourceOrder;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        // check if selected shipping method is delyvax
        if (strpos($order->getShippingMethod(), DelyvaxHelper::DELYVAX_SHIPMENT_CODE) !== false) {
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

            $serviceCode = $this->_delyvaxHelper->getServiceCodeFromShippingMethod($order->getShippingMethod());

            // check payment method and set codAmount
            $codAmount = ($order->getPayment()->getMethod() === \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) ? $order->getGrandTotal() : 0;
            $cod = [
                "amount" => (string) $codAmount,
                "currency" => $order->getOrderCurrencyCode()
            ];
            $process = false;
            $createOrderResponse = $this->_delyvaxHelper->postCreateOrder($origin, $destination, $serviceCode, $cod, $OriginOrderNotes, $process);
            file_put_contents('var/log/orderPlaceAfter.txt', '\n--------------------------\createOrderResponse: \n'. $order->getIncrementId() . print_r($createOrderResponse, TRUE), FILE_APPEND);

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
                } catch (LocalizedException | \Exception $exception) {
                    $this->logger->critical($exception->getMessage());
                }
            }
        }

    }
}
