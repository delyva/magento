<?php
namespace Delyvax\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;

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

    protected $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        DelyvaxHelper $delyvaxHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_quoteRepository = $quoteRepository;
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        // check if selected shipping method is delyvax
        if (strpos($order->getShippingMethod(), DelyvaxHelper::DELYVAX_SHIPMENT_CODE) !== false) {
            try {
                $quote = $this->_quoteRepository->get($order->getQuoteId());
            } catch (NoSuchEntityException $e) {
                $this->logger->debug($e->getMessage()); die();
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

            $destinationScheduledAt = $this->_delyvaxHelper->getOrderDestinationScheduledAt();
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
            $process = true;
            $createOrderResponse = $this->_delyvaxHelper->postCreateOrder($origin, $destination, $serviceCode, $cod, $OriginOrderNotes, $process);
            file_put_contents('var/log/orderPlaceAfter.txt', '\n--------------------------\$createOrderResponse: \n'.print_r($createOrderResponse, TRUE), FILE_APPEND);
        }
        
    }
}
