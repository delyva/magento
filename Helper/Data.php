<?php

namespace Delyvax\Shipment\Helper;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as ResourceOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Quote\Model\Quote\Address\RateRequest;


class Data extends AbstractHelper
{
    const DELYVAX_SHIPMENT_CODE = 'delyvax_shipment';

    const DELYVAX_CONFIG_PATH = 'carriers/delyvax_shipment/';
    const DELYVAX_CREDENTIALS_PATH = 'carriers/delyvax_shipment/credentials/';
    const DELYVAX_SETTINGS_PATH = 'carriers/delyvax_shipment/settings/';
    const DELYVAX_RATE_PATH = 'carriers/delyvax_shipment/shipping_rate_adjustment/';

    const DELYVAX_API_ENDPOINT = 'https://api.delyva.app';
    const DELYVAX_CDN_URL = 'https://cdn.delyva.app/';

    const STATUS = 'status';
    const STATUS_CODE = 'status_code';
    const RESPONSE = 'response';
    const ERROR_RESPONSE = 'error_response';

    const DELYVAX_SHIPMENT_STATUS_DRAFT = 'draft';

    /**
     * @var Curl
     */
    private $clientFactory;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var TimezoneInterface
     */
    protected $_timezoneInterface;

    /**
     * @var StoreInformation
     */
    protected $_storeInformation;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     *  @var WriterInterface
     */
    protected $_configWriter;

    /**
     * CountryFactory
     */
    protected $_countryFactory;

    /**
     * @var ResourceOrder
     */
    private $_resourceOrder;

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

    /**
     * @var ProductRepository
     */
    protected $_productRepository;
    
    /**
     * Data constructor.
     * @param Context $context
     * @param CurlFactory $clientFactory
     * @param DateTime $date
     * @param TimezoneInterface $timezoneInterface
     * @param StoreInformation $storeInformation
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param CountryFactory $countryFactory
     * @param ResourceOrder $resourceOrder
     * @param ConvertOrder $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     * @param TrackFactory $trackFactory
     * @param ProductRepository $productRepository
     */
    public function __construct(
        Context $context,
        CurlFactory $clientFactory,
        DateTime $date,
        TimezoneInterface $timezoneInterface,
        StoreInformation $storeInformation,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        CountryFactory $countryFactory,
        ResourceOrder $resourceOrder,
        ConvertOrder $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        TrackFactory $trackFactory,
        ProductRepository $productRepository
    )
    {
        parent::__construct($context);
        $this->clientFactory = $clientFactory;
        $this->_date = $date;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_storeInformation = $storeInformation;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_configWriter = $configWriter;
        $this->_countryFactory = $countryFactory;
        $this->_resourceOrder = $resourceOrder;
        $this->_convertOrder = $convertOrder;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_trackFactory = $trackFactory;
        $this->_productRepository = $productRepository;
    }

    /**
     * @param $destination array
     * @param $weight array
     * @return array
     */
    public function getPriceQuote(array $destination, array $weight): array
    {
        $apiUrl = self::DELYVAX_API_ENDPOINT . '/service/instantQuote';
        $delyvaxConfig = $this->getDelyvaxConfig();
        $origin = $this->getOriginShippingAddress();
        $postRequestArr = [
            'companyId' => $delyvaxConfig['delyvax_company_id'],
            'customerId' => $delyvaxConfig['delyvax_customer_id'],
            'origin' => $origin,
            'destination' => $destination,
            "weight" => $weight,
            "itemType" => $delyvaxConfig['delyvax_item_type']
        ];
        return $this->makeRequest($apiUrl, $postRequestArr, 'getPriceQuote');
    }

    /**
     * @return array
     */
    public function getDelyvaxConfig() : array
    {
        return [
            'title' => $this->scopeConfig->getValue(self::DELYVAX_CONFIG_PATH . 'title'),
            'delyvax_api_token' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_token'),
            'delyvax_api_webhook_enable' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_enable'),
            'delyvax_company_code' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_company_code'),
            'delyvax_company_id' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_company_id'),
            'delyvax_company_name' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_company_name'),
            'delyvax_customer_id' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_customer_id'),
            'delyvax_user_id' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_user_id'),
            'delyvax_ext_id_type' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_ext_id_type'),
            'delyvax_item_type' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_item_type'),
            'create_shipment_on_paid' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'create_shipment_on_paid'),
            'change_order_status' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'change_order_status'),
            'delyvax_processing_days' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_processing_days'),
            'delyvax_processing_hours' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_processing_hours'),
            'delyvax_split_tasks' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_split_tasks'),
            'delyvax_task_item_type' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_task_item_type'),
            'delyvax_weight_consideration' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_weight_consideration'),
            'delyvax_volumetric_weight_constant' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_volumetric_weight_constant'),
            'delyvax_rate_adjustment_flat' => $this->scopeConfig->getValue(self::DELYVAX_RATE_PATH . 'delyvax_rate_adjustment_flat'),
            'delyvax_rate_adjustment_percentage' => $this->scopeConfig->getValue(self::DELYVAX_RATE_PATH . 'delyvax_rate_adjustment_percentage'),
            'delyvax_rate_adjustment_type' => $this->scopeConfig->getValue(self::DELYVAX_RATE_PATH . 'delyvax_rate_adjustment_type')
        ];
    }

    /**
     * @return array
     */
    public function getDelyvaxWebhookConfig() : array
    {
//        $this->_configWriter->save(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_updated_id', '&&&', $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
        return [
            'delyvax_api_webhook_enable' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_enable'),
            'delyvax_api_webhook_order_created_id' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_created_id'),
            'delyvax_api_webhook_order_failed_id' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_failed_id'),
            'delyvax_api_webhook_order_updated_id' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_updated_id'),
            'delyvax_api_webhook_order_tracking_update_id' => $this->scopeConfig->getValue(self::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_tracking_update_id')
        ];
    }

    /**
     * @return array
     */
    public function getOriginShippingAddress() : array
    {
        return [
            'address1' => $this->scopeConfig->getValue('shipping/origin/street_line1'),
            'address2' => $this->scopeConfig->getValue('shipping/origin/street_line2'),
            'city' => $this->scopeConfig->getValue('shipping/origin/city'),
            'state' => $this->scopeConfig->getValue('shipping/origin/region_id'),
            'postcode' => $this->scopeConfig->getValue('shipping/origin/postcode'),
            'country' => $this->scopeConfig->getValue('shipping/origin/country_id'),
        ];
    }

    /**
     * @param RateRequest $request
     * @return int | float
     */
    public function calculateWeightBasedOnDelyvaxSettings(RateRequest $request)
    {
        $totalWeight = $request->getPackageWeight();
        $delyvaxConfig = $this->getDelyvaxConfig();
        if ($delyvaxConfig['delyvax_weight_consideration'] == 'ACTUAL') {
            return $totalWeight;
        } else {
            $totalDimension = 0;
            foreach ($request->getAllItems() as $item) {
                $product = $this->_productRepository->getById($item->getProduct()->getId());
                $totalDimension = $totalDimension + ($product->getLength() * $product->getWidth() * $product->getHeight());
            }
            $totalActualWeight = $totalWeight;
            $volumetricConstant = $delyvaxConfig['delyvax_volumetric_weight_constant'];

            //calculate volumetric weight
            $totalVolumetricWeight = ($totalDimension > 0) ? $totalDimension / $volumetricConstant : $totalActualWeight;
            if ($delyvaxConfig['delyvax_weight_consideration'] == 'VOL') {
                return $totalVolumetricWeight;
            } else {
                $totalWeight = ($totalActualWeight > $totalVolumetricWeight) ? $totalActualWeight : $totalVolumetricWeight;
                return $totalWeight;
            }
        }
        // file_put_contents('var/log/request_allItems.json', '--------------------------\nrequest->getAllItems(): \n'.json_encode($arr, JSON_PRETTY_PRINT).PHP_EOL, FILE_APPEND);
        
    }

    /**
     * @param Quote $quote
     * @param string $orderCurrencyCode
     * @return array
     */
    public function getInventoriesToCreateOrderByQuote(Quote $quote, string $orderCurrencyCode): array
    {
        $inventories = array();
        $delyvaxConfig = $this->getDelyvaxConfig();
        foreach ($quote->getAllVisibleItems() as $item) {
            // Skip if item type is Virtual/Downloadable, (removed condition - or item weight is less than or equal to 0, that is $item->getWeight() <= 0
            if ($item->getProductType() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
                || $item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL) {
                continue;
            }
            $inventories[] = [
                "name" => $item->getName(),
                "type" => $delyvaxConfig['delyvax_item_type'],
                "price" => array(
                    "amount" => (string)$item->getPrice(),
                    "currency" => $orderCurrencyCode,
                ),
                "weight" => array(
                    "value" => (int) ($item->getWeight() * $item->getQty()),
                    "unit" => "kg"
                ),
                "quantity" => (int) $item->getQty(),
                "description" => '[Sku:'. $item->getSku() . ' - Product Type:'. $item->getProductType() . ']'
            ];
        }
        return $inventories;
    }

    /**
     * @return string
     */
    public function getOrderOriginScheduledAt(): string
    {
        $delyvaxConfig = $this->getDelyvaxConfig();
        // Convert days into hours, and add up processing hours into it
        $processingHours = $delyvaxConfig['delyvax_processing_days'] * 24 + $delyvaxConfig['delyvax_processing_hours'];
        $currentDateTime = $this->_timezoneInterface->date()->format('c');
        return $this->_timezoneInterface->date(strtotime($currentDateTime." +{$processingHours} hours"))->format('c');
    }

    /**
     * Adding 24 hours to Origin Scheduled At
     * @return string
     */
    public function getOrderDestinationScheduledAt(): string
    {
        $originScheduledAt = $this->getOrderOriginScheduledAt();
        $processingHours = 24;
        return $this->_timezoneInterface->date(strtotime($originScheduledAt." +{$processingHours} hours"))->format('c');
    }

    /**
     * @param string $shippingMethod
     * @return string
     */
    public function getServiceCodeFromShippingMethod(string $shippingMethod): string
    {
        return str_replace(self::DELYVAX_SHIPMENT_CODE . '_', '', $shippingMethod);
    }

    /**
     * @return array
     */
    public function getOrderOriginContact(): array
    {
        $storeInfo = $this->_storeInformation->getStoreInformationObject($this->_storeManager->getStore());
        $originShippingAddress = $this->getOriginShippingAddress();
        return [
            'name' => $storeInfo->getName(),
            "email" => $this->getStoreEmail(),
            "phone" => $storeInfo->getPhone(),
            "mobile" => $storeInfo->getPhone(),
            "address1" => $originShippingAddress['address1'],
            "address2" => $originShippingAddress['address2'],
            "city" => $originShippingAddress['city'],
            "state" => $originShippingAddress['state'],
            "postcode" => $originShippingAddress['postcode'],
            "country" => $originShippingAddress['country']
        ];
    }

    /**
     * @return string
     */
    public function getStoreEmail(): string
    {
        return $this->_scopeConfig->getValue(
            'trans_email/ident_sales/email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param string $countryCode
     * @return string
     */
    public function getCountryNameByCode(string $countryCode): string
    {
        $country = $this->_countryFactory->create()->loadByCode($countryCode);
        return $country->getName();
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getDestinationContact(Order $order): array
    {
        $shippingAddress = $order->getShippingAddress()->getData();
        $address = explode(PHP_EOL, $shippingAddress['street']);
        return [
            'name' => $shippingAddress['firstname'] . ' ' . $shippingAddress['lastname'],
            "email" => $shippingAddress['email'],
            "phone" => $shippingAddress['telephone'],
            "mobile" => $shippingAddress['telephone'],
            "address1" => (array_key_exists(0, $address)) ? $address[0] : $shippingAddress['street'],
            "address2" => (array_key_exists(1, $address)) ? $address[1] : '-',
            "city" => $shippingAddress['city'],
            "state" => $shippingAddress['region'],
            "postcode" => $shippingAddress['postcode'],
            "country" => $shippingAddress['country_id']
        ];
    }

    /**
     * @param array $origin
     * @param array $destination
     * @param string $serviceCode
     * @param array $cod
     * @param string $orderNotes
     * @param bool $process
     * @return array
     */
    public function postCreateOrder(array $origin, array $destination, string $serviceCode, array $cod, string $orderNotes, bool $process = false): array
    {
        $apiUrl = self::DELYVAX_API_ENDPOINT . '/order';
        $delyvaxConfig = $this->getDelyvaxConfig();
        $postRequestArr = [
            "customerId" => $delyvaxConfig['delyvax_customer_id'],
            'process' => $process,
            'serviceCode' => $serviceCode,
            'origin' => $origin,
            'destination' => $destination,
            'note' => $orderNotes,
            'cod' => $cod,
            'source' => 'magento'
        ];

        return $this->makeRequest($apiUrl, $postRequestArr, 'postCreateOrder');
    }

    /**
     * @param string $delyvaxOrderId
     * @param string $serviceCode
     * @return array
     */
    public function processDelyvaxOrder(string $delyvaxOrderId, string $serviceCode): array
    {
        $apiUrl = self::DELYVAX_API_ENDPOINT . '/order/:orderId/process';
        $apiUrl = str_replace(":orderId", $delyvaxOrderId, $apiUrl);
        $postRequestArr = [
            "orderId" => $delyvaxOrderId,
            "serviceCode" => $serviceCode,
            "skipQueue" => true,
        ];
        return $this->makeRequest($apiUrl, $postRequestArr, 'processDelyvaxOrder');
    }

    /**
     * @param Order $order
     * @param bool $createOrderShipment
     * @throws LocalizedException
     */
    public function processDelyvaxOrderIfDraft(Order $order, $createOrderShipment = false)
    {
        if ($order->getDelyvaxOrderId() != NULL && $order->getDelyvaxOrderStatus() == self::DELYVAX_SHIPMENT_STATUS_DRAFT) {
            $serviceCode = $this->getServiceCodeFromShippingMethod($order->getShippingMethod());
            $processOrderResponse = $this->processDelyvaxOrder($order->getDelyvaxOrderId(), $serviceCode);
            file_put_contents('var/log/orderPlaceAfter.txt', '\n--------------------------\processOrderResponse: \n'. $order->getIncrementId() . print_r($processOrderResponse, TRUE), FILE_APPEND);

            if ($processOrderResponse[self::STATUS]) {
                $processOrderResponse = $processOrderResponse[self::RESPONSE];

                $orderRespData['delyvax_consignment_number'] = NULL;
                if (array_key_exists('data', $processOrderResponse)) {
                    $orderRespData['delyvax_consignment_number'] = (array_key_exists('consignmentNo', $processOrderResponse['data'])) ? $processOrderResponse['data']['consignmentNo'] : NULL;
                    $orderRespData['delyvax_order_status'] = (array_key_exists('status', $processOrderResponse['data'])) ? $processOrderResponse['data']['status'] : NULL;
                }

                try {
                    $order->addCommentToStatusHistory('Shipment is ready to collect by ' . $order->getShippingDescription(), 'dx-preparing', false);
                    $order->addData($orderRespData);
                    $this->_resourceOrder->save($order);
                } catch (LocalizedException | \Exception $exception) {
                    $this->_logger->critical($exception->getMessage());
                }

                if ($createOrderShipment) {
                    // Check if order can be shipped or has already shipped
                    if (!$order->canShip()) {
                        throw new LocalizedException(
                            __('You can\'t create an shipment.')
                        );
                    }
                    $shipment = $this->_convertOrder->toShipment($order);
                    // Loop through order items
                    foreach ($order->getAllItems() AS $orderItem) {
                        // Check if order item has qty to ship or is virtual
                        if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                            continue;
                        }
                        $qtyShipped = $orderItem->getQtyToShip();
                        // Create shipment item with qty
                        $shipmentItem = $this->_convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
                        // Add shipment item to shipment
                        $shipment->addItem($shipmentItem);
                    }
                    // Register shipment
                    $shipment->register();
                    $shipment->getOrder()->setIsInProcess(true);

                    try {
                        // Save created shipment and order
                        $shipment->save();
                        $shipment->getOrder()->save();
                        // Send email
                        $this->_shipmentNotifier->notify($shipment);
                    } catch (\Exception $e) {
                        throw new LocalizedException(
                            __($e->getMessage())
                        );
                    }

                    $data = [
                        'carrier_code' => self::DELYVAX_SHIPMENT_CODE,
                        'title' => $order->getShippingDescription(),
                        'number' => $order->getDelyvaxConsignmentNumber(),
                    ];
                    $this->setDelyvaxShipmentTrackingInfo($shipment, $order);
                }
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param Order $order
     * @throws LocalizedException
     */
    public function setDelyvaxShipmentTrackingInfo(\Magento\Sales\Model\Order\Shipment $shipment, Order $order)
    {
        $data = [
            'carrier_code' => self::DELYVAX_SHIPMENT_CODE,
            'title' => $order->getShippingDescription(),
            'number' => $order->getDelyvaxConsignmentNumber(),
        ];
        try {
            $track = $this->_trackFactory->create()->addData($data);
            $shipment->addTrack($track)->save();
        } catch (\Exception $e) {
            throw new LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * @param string $eventName
     * @return array
     */
    public function postCreateWebhook(string $eventName): array
    {

        $apiUrl = self::DELYVAX_API_ENDPOINT . "/webhook/";
        $storeWebhookUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) . 'delyvax/webhook/tracking';
        $postRequestArr = [
            "event" => $eventName,
            "url" => $storeWebhookUrl,
        ];
        return $this->makeRequest($apiUrl, $postRequestArr, 'postCreateWebhook');
    }

    /**
     * @param string $apiUrl
     * @param array $postRequestArr
     * @param string $requestActionName
     * @return array
     */
    public function makeRequest(string $apiUrl, array $postRequestArr, string $requestActionName): array
    {
        $delyvaxConfig = $this->getDelyvaxConfig();
        $accessToken = $delyvaxConfig['delyvax_api_token'];

        /**@var $curl \Magento\Framework\HTTP\Client\Curl */
        $curl = $this->clientFactory->create();
        $curl->addHeader("Authorization", "Bearer $accessToken");
        $curl->addHeader("content-type", "application/json");
        $curl->addHeader("X-Delyvax-Access-Token", $delyvaxConfig['delyvax_api_token']);
        $curl->post($apiUrl, json_encode($postRequestArr, JSON_UNESCAPED_SLASHES));

        $this->_logger->debug(var_export('-------------' . $requestActionName . '-------------', true));
        $this->_logger->debug(var_export(json_encode($postRequestArr, JSON_UNESCAPED_SLASHES), true));
        $this->_logger->debug(var_export($curl->getStatus(), true));
        $this->_logger->debug(var_export($curl->getBody(), true));

        if ($curl->getStatus() == 200 || $curl->getStatus() == 100) {
            return [
                self::STATUS => true,
                self::STATUS_CODE => $curl->getStatus(),
                self::RESPONSE => json_decode($curl->getBody(), true)
            ];
        } else {
            return [
                self::STATUS => false,
                self::STATUS_CODE => $curl->getStatus(),
                self::RESPONSE => json_decode($curl->getBody(), true)
            ];
        }
    }

    /**
     * @return array
     */
    public function getDelyvaxStatusCodeAndLabel(): array
    {
        return [
            'dx-preparing' => 'Preparing',
            'dx-ready-to-collect' => 'Ready to collect',
            'dx-courier-accepted' => 'Courier accepted',
            'dx-start-collecting' => 'Pending pick up',
            'dx-collected' => 'Pick up complete',
            'dx-failed-collection' => 'Pick up failed',
            'dx-start-delivery' => 'On the way for delivery',
            'dx-failed-delivery' => 'Delivery failed',
            'dx-request-refund' => 'Request refund'
        ];
    }

}
