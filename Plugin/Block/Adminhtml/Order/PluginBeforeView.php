<?php

namespace Delyvax\Shipment\Plugin\Block\Adminhtml\Order;

use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Psr\Log\LoggerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class PluginBeforeView
{
    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    public function __construct(
        DelyvaxHelper $delyvaxHelper,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
    }

    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\View $view)
    {
        $order = $this->orderRepository->get($view->getOrderId());
        $labelAllowedStatuses = ['created'];
        if (in_array($order->getDelyvaxOrderStatus(), $labelAllowedStatuses)) {
            $message ='Are you sure you want to print label?';
            $delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
            $companyId = $delyvaxConfig['delyvax_company_id'];
            $printLabelUrl = 'https://api.delyva.app/v1.0/order/'.$order->getDelyvaxOrderId().'/label?companyId='.$companyId;
            $view->addButton(
                'order_myaction',
                [
                    'label' => __('Print Shipment Label'),
                    'class' => 'myclass',
                    'onclick' => "confirmSetLocation('{$message}', '{$printLabelUrl}')"
                ]
            );
        }
    }

}
