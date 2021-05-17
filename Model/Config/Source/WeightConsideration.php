<?php

namespace Delyvax\Shipment\Model\Config\Source;

class WeightConsideration implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'BEST', 'label' => __('BEST')],
            ['value' => 'ACTUAL', 'label' => __('ACTUAL')],
            ['value' => 'VOL', 'label' => __('VOL')]
        ];
    }
}
