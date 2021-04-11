<?php

namespace Delyvax\Shipment\Model\Config\Source;

class RateAdjustmentType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'Markup', 'label' => __('Markup')],
            ['value' => 'Discount', 'label' => __('Discount')]
        ];
    }
}
