<?php

namespace Delyvax\Shipment\Model\Config\Source;

class RateAdjustmentForm implements \Magento\Framework\Option\ArrayInterface
{
    const PERCENTAGE = 'Percentage';
    const FLAT = 'Flat';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::PERCENTAGE, 'label' => __('Percentage')],
            ['value' => self::FLAT, 'label' => __('Flat')]
        ];
    }
}
