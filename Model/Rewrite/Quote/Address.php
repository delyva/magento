<?php

namespace Delyvax\Shipment\Model\Rewrite\Quote;

class Address extends \Magento\Quote\Model\Quote\Address
{
    /**
     * Return 2 letter state code if available, otherwise full region name
     *
     * @return string
     */
    public function getRegionCode()
    {
        $regionId = $this->getData('region_id');
        $region = $this->getData('region');

        if (!$regionId && is_numeric($region)) {
            if ($this->getRegionModel($region)->getCountryId() == $this->getCountryId()) {
                $this->setData('region_code', $this->getRegionModel($region)->getCode());
            }
        } elseif ($regionId) {
            if ($this->getRegionModel($regionId)->getCountryId() == $this->getCountryId()) {
                $this->setData('region_code', $this->getRegionModel($regionId)->getCode());
            }
        } elseif (is_string($region)) {
            $this->setData('region_code', $region);
        } elseif (is_array($region)) {
            $this->setData('region_code', $region['region']);
        }
        return $this->getData('region_code');
    }

}
