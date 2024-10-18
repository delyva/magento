<?php

namespace Delyvax\Shipment\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class CartPriceRules implements ArrayInterface
{
    protected $_ruleCollectionFactory;

    public function __construct(RuleCollectionFactory $ruleCollectionFactory)
    {
        $this->_ruleCollectionFactory = $ruleCollectionFactory;
    }

    public function toOptionArray()
    {
        $rules = $this->_ruleCollectionFactory->create();
        $options = [];
        $options[] = ['value' => '', 'label' => __('None')];
        foreach ($rules as $rule) {
            $options[] = ['value' => $rule->getId(), 'label' => $rule->getName()];
        }
        return $options;
    }
}
