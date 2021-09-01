<?php

namespace Delyvax\Shipment\Model\Config\Source;

class ProcessingTime implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '08', 'label' => __('08:00 AM')],
            ['value' => '09', 'label' => __('09:00 AM')],
            ['value' => '10', 'label' => __('10:00 AM')],
            ['value' => '11', 'label' => __('11:00 AM')],
            ['value' => '12', 'label' => __('12:00 PM')],
            ['value' => '13', 'label' => __('01:00 PM')],
            ['value' => '14', 'label' => __('02:00 PM')],
            ['value' => '15', 'label' => __('03:00 PM')],
            ['value' => '16', 'label' => __('04:00 PM')],
            ['value' => '17', 'label' => __('05:00 PM')],
            ['value' => '18', 'label' => __('06:00 PM')],
            ['value' => '19', 'label' => __('07:00 PM')],
            ['value' => '20', 'label' => __('08:00 PM')]
        ];
    }
}
