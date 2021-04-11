<?php

namespace Delyvax\Shipment\Model\Config\Source;

class DefaultItemType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'DOCUMENT', 'label' => __('DOCUMENT')],
            ['value' => 'PARCEL', 'label' => __('PARCEL')],
            ['value' => 'FOOD', 'label' => __('FOOD')],
            ['value' => 'PACKAGE', 'label' => __('PACKAGE')]
        ];
    }
}
