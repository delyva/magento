<?php
namespace Delyvax\Shipment\Model\Carrier;

use Delyvax\Shipment\Model\Config\Source\RateAdjustmentForm;
use Delyvax\Shipment\Model\Config\Source\RateAdjustmentType;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Shipping\Model\Rate\Result;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;

class Shipping extends \Magento\Shipping\Model\Carrier\AbstractCarrierOnline implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'delyvax_shipment';

    /**
     * Shipping constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param DelyvaxHelper $delyvaxHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory,
        \Psr\Log\LoggerInterface                                    $logger,
        Security                                                    $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory            $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory                  $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory              $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory        $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory       $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory                      $regionFactory,
        \Magento\Directory\Model\CountryFactory                     $countryFactory,
        \Magento\Directory\Model\CurrencyFactory                    $currencyFactory,
        \Magento\Directory\Helper\Data                              $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface        $stockRegistry,
        DelyvaxHelper                                               $delyvaxHelper,
        \Magento\Customer\Model\Session                             $customerSession,
        RuleFactory                                                 $ruleFactory,
        array                                                       $data = []
    )
    {
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->customerSession = $customerSession;
        $this->rule = $ruleFactory;
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }

    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    protected $rule;

    /**
     * get allowed methods
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    /**
     * @return Result
     */
    public function getDelyvaxFlatShippingMethod()
    {
        $result = $this->_rateFactory->create();
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('delyvax_flat_rate_name'));
        $shippingCost = (float)$this->getConfigData('delyvax_flat_rate');
        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);
        $result->append($method);
        return $result;
    }

    function isQuoteMatchingRule(Quote $quote)
    {
        $ruleId = $this->_delyvaxHelper->getDelyvaxConfig()["delyvax_rate_adjustment_rule"];
        $rule = $this->rule->create()->load($ruleId);

        if (!$rule->getId()) {
            throw new \Exception("Rule with ID $ruleId does not exist.");
        }

        // Validate cart-level conditions
        if (!$rule->getConditions()->validate($quote)) {
            return false;
        }

        // Validate item-level conditions
        foreach ($quote->getAllItems() as $item) {
            if (!$rule->getActions()->validate($item)) {
                return false;
            }
        }

        return true;
    }




    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
        if ($delyvaxConfig['show_dynamic_rates_on_checkout'] == 0) {
            return $this->getDelyvaxFlatShippingMethod();
        }

        $streetAddress = preg_split('/\r\n|\r|\n/', $request->getDestStreet());
        $st1 = (isset($streetAddress[0])) ? $streetAddress[0] : '-';
        $st2 = '-';
        if (isset($streetAddress[1])) {
            $st2 = $streetAddress[1];
            if (isset($streetAddress[2])) {
                $st2 = $st2 . ' ' . $streetAddress[2];
            }
        }

        $destination = [
            "address1" => (string)$st1,
            "address2" => (string)$st2,
            "city" => (string)$request->getDestCity(),
            "state" => ($request->getDestRegionId()) ? $this->_delyvaxHelper->getStateById($request->getDestRegionId()) : $request->getDestRegionCode(),
            "postcode" => $request->getDestPostcode(),
            "country" => $request->getDestCountryId()
        ];

        $weight = [
            "unit" => "kg",
            "value" => $request->getPackageWeight()
        ];

        $inventory = $this->_delyvaxHelper->getInventoryNodeForCartItemsWeightVolume($request);

        $rates = $this->_delyvaxHelper->getPriceQuote($destination, $weight, $inventory);

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateFactory->create();

        if ($rates[DelyvaxHelper::STATUS]) {
            $services = $rates[DelyvaxHelper::RESPONSE]['data']['services'];
            foreach ($services as $shipper) {
                if (isset($shipper['service']['name'])) {
                    $cost = $shipper['price']['amount'];

                    if ($delyvaxConfig['delyvax_rate_adjustment_enabled']) {
                        $items = $request->getAllItems();
                        $ruleId = $delyvaxConfig['delyvax_rate_adjustment_rule'];
                        $shipperCode = $shipper['service']['code'];
                        $allowedShippers = explode(',', $delyvaxConfig['delyvax_rate_adjustment_service_providers']);

                        if (in_array($shipperCode, $allowedShippers) || in_array('*', $allowedShippers)) {
                            if (is_null($ruleId) || $this->isQuoteMatchingRule($items[0]->getQuote())) {
                                foreach ($items as $item) {
                                    $adjustmentAmount = $delyvaxConfig['delyvax_rate_adjustment_amount'] ?? 0;

                                    if ($delyvaxConfig['delyvax_rate_adjustment_form'] == RateAdjustmentForm::PERCENTAGE) {
                                        $percentRate = ($adjustmentAmount / 100) * $shipper['price']['amount'];
                                        if ($delyvaxConfig['delyvax_rate_adjustment_type'] == RateAdjustmentType::MARKUP) {
                                            // Markup: cost = original_cost + (adjustmentAmount / 100) * original_cost
                                            $cost = round($shipper['price']['amount'] + $percentRate, 2);
                                        } else {
                                            // Discount: cost = original_cost - (adjustmentAmount / 100) * original_cost
                                            $cost = round($shipper['price']['amount'] - $percentRate, 2);
                                        }
                                    } elseif ($delyvaxConfig['delyvax_rate_adjustment_form'] == RateAdjustmentForm::FLAT) {
                                        if ($delyvaxConfig['delyvax_rate_adjustment_type'] == RateAdjustmentType::MARKUP) {
                                            // Markup: cost = original_cost + adjustmentAmount
                                            $cost = round($shipper['price']['amount'] + $adjustmentAmount, 2);
                                        } else {
                                            // Discount: cost = original_cost - adjustmentAmount
                                            $cost = round($shipper['price']['amount'] - $adjustmentAmount, 2);
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    if ($cost < 0) {
                        $cost = 0.00;
                    }
                    $logo = null;
                    if (array_key_exists('serviceCompany', $shipper['service']) &&
                        array_key_exists('logo', $shipper['service']['serviceCompany']) &&
                        $shipper['service']['serviceCompany']['logo']) {
                        $logo = DelyvaxHelper::DELYVAX_CDN_URL . $shipper['service']['serviceCompany']['logo'];
                    }

                    $rate = [
                        'id' => $shipper['service']['code'],
                        'label' => $shipper['service']['name'],
                        'cost' => $cost,
                        'logo' => $logo,
                        'taxes' => 'false',
                        'calc_tax' => 'per_order',
                        'meta_data' => array(
                            'service_code' => $shipper['service']['code'],
                        )
                    ];
                    $result->append($this->_createShippingMethod($rate));
                }
            }
        } else {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier('DelyvaX Carrier');
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($this->getConfigData('specificerrmsg'));
            $result->append($error);
        }

        return $result;
    }

    /**
     * Set specified rate and return as a method
     * @param $rate array
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected function _createShippingMethod(array $rate): \Magento\Quote\Model\Quote\Address\RateResult\Method
    {
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->_rateMethodFactory->create();
        // Must be the unique code specified
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($rate['id']);
        $method->setMethodTitle($rate['label']);
        $method->setMethodDescription($rate['label']);
        $shippingCost = (float)$rate['cost'];
        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);
        return $method;
    }

    /**
     * Check if carrier has shipping tracking option available
     * @return boolean
     */
    public function isTrackingAvailable(): bool
    {
        return true;
    }

    /**
     * Get tracking
     *
     * @param string|string[] $trackings
     * @return \Magento\Shipping\Model\Tracking\Result
     */
    public function getTracking($trackings): \Magento\Shipping\Model\Tracking\Result
    {
        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }
        $result = $this->_trackFactory->create();
        $companyCode = $this->getConfigData('credentials/delyvax_company_code');
        foreach ($trackings as $tracking) {
            $status = $this->_trackStatusFactory->create();
            $status->setCarrier($this->_code);
            $status->setCarrierTitle($this->getConfigData('title'));
            $status->setTracking($tracking);
            $status->setPopup(1);
            $status->setUrl(
                "https://{$companyCode}.delyva.app/customer/strack?trackingNo={$tracking}"
            );
            $result->append($status);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        /*$result = new \Magento\Framework\DataObject();
        $shippingLabelContent = $request;
        $result->setShippingLabelContent($shippingLabelContent);
        return $result;*/
    }

    /**
     * Check if carrier has shipping label option available
     *
     * @return boolean
     */
    public function isShippingLabelsAvailable()
    {
        return true;
    }

    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return true;
    }
}
