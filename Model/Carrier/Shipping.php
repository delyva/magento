<?php
namespace Delyvax\Shipment\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;

class Shipping extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'delyvax_shipment';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Shipping constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface          $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory  $rateErrorFactory
     * @param \Psr\Log\LoggerInterface                                    $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory                  $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param DelyvaxHelper $delyvaxHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        DelyvaxHelper $delyvaxHelper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->customerSession = $customerSession;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * get allowed methods
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
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
        $streetAddress = preg_split('/\r\n|\r|\n/', $request->getDestStreet());
        $st1 = (isset($streetAddress[0])) ? $streetAddress[0] : '-';
        $st2 = '-';
        if (isset($streetAddress[1])) {
            $st2 = $streetAddress[1];
            if (isset($streetAddress[2])) {
                $st2 = $st2 . ' ' . $streetAddress[2];
            }
        }
        // file_put_contents('var/log/shipping_request.json', '--------------------------\nRequest: \n'.json_encode($request->getData(), JSON_PRETTY_PRINT).PHP_EOL, FILE_APPEND);

        $destination = [
            "address1" => $st1,
            "address2" => $st2,
            "city" => $request->getDestCity(),
            "state" => $request->getDestRegionCode(),
            "state2" => $request->getDestRegionId(),
            "postcode" => $request->getDestPostcode(),
            "country" => $request->getDestCountryId()
        ];
        $weight = [
            "unit" => "kg",
            "value" => $request->getPackageWeight()
        ];

        $rates = $this->_delyvaxHelper->getPriceQuote($destination, $weight);

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->_rateResultFactory->create();

        if ($rates[DelyvaxHelper::STATUS]) {
            $services = $rates[DelyvaxHelper::RESPONSE]['data']['services'];
            foreach ($services as $shipper) {
                if (isset($shipper['service']['name'])) {
                    $delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
                    $ra_percentage = $delyvaxConfig['delyvax_rate_adjustment_percentage'] ?? 1;
                    $percentRate = $ra_percentage / 100 * $shipper['price']['amount'];
                    $flatRate = $delyvaxConfig['delyvax_rate_adjustment_flat'] ?? 0;
                    if ($delyvaxConfig['delyvax_rate_adjustment_type'] == \Delyvax\Shipment\Model\Config\Source\RateAdjustmentType::DISCOUNT) {
                        $cost = round($shipper['price']['amount'] - $percentRate - $flatRate, 2);
                    } else {
                        $cost = round($shipper['price']['amount'] + $percentRate + $flatRate, 2);
                    }
                    if ($cost < 0) { $cost = 0.00; }
                    $rate = [
                        'id' => $shipper['service']['code'],
                        'label' => $shipper['service']['name'],
                        'cost' => $cost,
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
    protected function _createShippingMethod (array $rate): \Magento\Quote\Model\Quote\Address\RateResult\Method
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
}
