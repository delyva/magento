<?php

namespace Delyvax\Shipment\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Quote\Model\Quote;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Information as StoreInformation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\CountryFactory;
use Magento\Sales\Model\Order;

class Data extends AbstractHelper
{
    const DELYVAX_SHIPMENT_CODE = 'delyvax_shipment';

    const DELYVAX_CONFIG_PATH = 'carriers/delyvax_shipment/';
    const DELYVAX_CREDENTIALS_PATH = 'carriers/delyvax_shipment/credentials/';
    const DELYVAX_SETTINGS_PATH = 'carriers/delyvax_shipment/settings/';
    const DELYVAX_RATE_PATH = 'carriers/delyvax_shipment/shipping_rate_adjustment/';

    const DELYVAX_API_ENDPOINT = 'https://api.delyva.app';

    const STATUS = 'status';
    const STATUS_CODE = 'status_code';
    const RESPONSE = 'response';
    const ERROR_RESPONSE = 'error_response';

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
     * CountryFactory
     */
    protected $_countryFactory;

    /**
     * Data constructor.
     * @param Context $context
     * @param CurlFactory $clientFactory
     * @param DateTime $date
     * @param TimezoneInterface $timezoneInterface
     * @param StoreInformation $storeInformation
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CountryFactory $countryFactory
     */
    public function __construct(
        Context $context,
        CurlFactory $clientFactory,
        DateTime $date,
        TimezoneInterface $timezoneInterface,
        StoreInformation $storeInformation,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        CountryFactory $countryFactory
    )
    {
        parent::__construct($context);
        $this->clientFactory = $clientFactory;
        $this->_date = $date;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_storeInformation = $storeInformation;
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_countryFactory = $countryFactory;
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

        $accessToken = $delyvaxConfig['delyvax_api_token'];

        /**@var $curl \Magento\Framework\HTTP\Client\Curl */
        $curl = $this->clientFactory->create();
        $curl->addHeader("Authorization", "Bearer $accessToken");
        $curl->addHeader("content-type", "application/json");
        $curl->addHeader("X-Delyvax-Access-Token", $delyvaxConfig['delyvax_api_token']);
        $curl->post($apiUrl, json_encode($postRequestArr, JSON_UNESCAPED_SLASHES));

//        $this->_logger->debug(var_export($postRequestArr, true));
//        $this->_logger->debug(var_export(json_decode($curl->getBody(), true), true));

        if ($curl->getStatus() == 200) {
            return [
                self::STATUS => true,
                self::STATUS_CODE => $curl->getStatus(),
                self::RESPONSE => json_decode($curl->getBody(), true)
            ];
        } else {
            return [
                self::STATUS => false,
                self::STATUS_CODE => $curl->getStatus()
            ];
        }
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
            'delyvax_processing_days' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_processing_days'),
            'delyvax_processing_hours' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_processing_hours'),
            'delyvax_split_tasks' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_split_tasks'),
            'delyvax_task_item_type' => $this->scopeConfig->getValue(self::DELYVAX_SETTINGS_PATH . 'delyvax_task_item_type'),
            'delyvax_rate_adjustment_flat' => $this->scopeConfig->getValue(self::DELYVAX_RATE_PATH . 'delyvax_rate_adjustment_flat'),
            'delyvax_rate_adjustment_percentage' => $this->scopeConfig->getValue(self::DELYVAX_RATE_PATH . 'delyvax_rate_adjustment_percentage')
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
     * @param Quote $quote
     * @param string $orderCurrencyCode
     * @return array
     */
    public function getInventoriesToCreateOrderByQuote(Quote $quote, string $orderCurrencyCode): array
    {
        $inventories = array();
        $delyvaxConfig = $this->getDelyvaxConfig();
        foreach ($quote->getAllVisibleItems() as $item) {
            // Skip if item type is Virtual/Downloadable, or item weight is less than or equal to 0
            if ($item->getProductType() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE
                || $item->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL
                || $item->getWeight() <= 0 ) {
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
            "address1" => isset($address[0]) ? $address[0] : $shippingAddress['street'],
            "address2" => isset($address[1]) ? $address[1] : '-',
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
            'cod' => $cod
        ];

        $accessToken = $delyvaxConfig['delyvax_api_token'];

        /**@var $curl \Magento\Framework\HTTP\Client\Curl */
        $curl = $this->clientFactory->create();
        $curl->addHeader("Authorization", "Bearer $accessToken");
        $curl->addHeader("content-type", "application/json");
        $curl->addHeader("X-Delyvax-Access-Token", $delyvaxConfig['delyvax_api_token']);
        $curl->post($apiUrl, json_encode($postRequestArr, JSON_UNESCAPED_SLASHES));

        $this->_logger->debug(var_export(json_encode($postRequestArr, JSON_UNESCAPED_SLASHES), true));
        $this->_logger->debug(var_export($curl->getBody(), true));

        if ($curl->getStatus() == 200) {
            return [
                self::STATUS => true,
                self::STATUS_CODE => $curl->getStatus(),
                self::RESPONSE => json_decode($curl->getBody(), true)
            ];
        } else {
            return [
                self::STATUS => false,
                self::STATUS_CODE => $curl->getStatus()
            ];
        }
    }
}
