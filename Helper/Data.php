<?php

namespace Delyvax\Shipment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\CurlFactory;

class Data extends AbstractHelper
{
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
     * Data constructor.
     * @param Context $context
     * @param CurlFactory $clientFactory
     */
    public function __construct(
        Context $context,
        CurlFactory $clientFactory
    )
    {
        parent::__construct($context);
        $this->clientFactory = $clientFactory;
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

        $this->_logger->debug(var_export($postRequestArr, true));
        $this->_logger->debug(var_export(json_decode($curl->getBody(), true), true));

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
}
