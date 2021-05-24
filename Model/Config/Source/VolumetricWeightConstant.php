<?php

namespace Delyvax\Shipment\Model\Config\Source;

class VolumetricWeightConstant implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '1000', 'label' => __('1000')],
            ['value' => '5000', 'label' => __('5000')],
            ['value' => '6000', 'label' => __('6000')]
        ];
    }
}
