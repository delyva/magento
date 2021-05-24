<?php

namespace Delyvax\Shipment\Model\Config\Source;

class RateAdjustmentType implements \Magento\Framework\Option\ArrayInterface
{
    const MARKUP = 'Markup';
    const DISCOUNT = 'Discount';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MARKUP, 'label' => __('Markup')],
            ['value' => self::DISCOUNT, 'label' => __('Discount')]
        ];
    }
}
