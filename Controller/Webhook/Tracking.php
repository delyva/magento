<?php

namespace Delyvax\Shipment\Controller\Webhook;

use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;

class Tracking extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var OrderResourceModel
     */
    protected $_orderResourceModel;

    /**
     * @var OrderRepository
     */
    protected $_orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * Tracking constructor.
     * @param DelyvaxHelper $delyvaxHelper
     * @param OrderResourceModel $orderResourceModel
     * @param OrderRepository $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Context $context
     */
    public function __construct(
        DelyvaxHelper $delyvaxHelper,
        OrderResourceModel $orderResourceModel,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Context $context
    )
    {
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->_orderResourceModel = $orderResourceModel;
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        parent::__construct($context);
    }

    /**
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute()
    {
        /*$delyvaxOrderId = 'b7c68d33-ddfe-4bbb-bb75-61524565fbdc';
        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('delyvax_order_id', $delyvaxOrderId, 'eq')->create();
        $orderList = $this->_orderRepository->getList($searchCriteria)->getItems();
        $order = reset($orderList);
//        $orderRepository = $this->_orderRepository->get($order->getId());
//        $orderRepository->setState('processing')->save();
        $order->setState('processing');
        $order->addCommentToStatusHistory('Order status changed to Pick up complete.', 'dx-collected', true);
        $this->_orderResourceModel->save($order);
        var_dump(get_class($order)); var_dump($order->getIncrementId()); var_dump($order->getDelyvaxConsignmentNumber()); var_dump($order->getDelyvaxOrderStatus()); var_dump($order->getStatus());  var_dump($order->getState());
        die();*/

        $raw = file_get_contents('php://input');
        if ($raw) {
            $data = json_decode($raw, true);
            if (isset($data)) {
                if (isset($data['orderId']) && isset($data['consignmentNo']) && isset($data['statusCode'])) {
                    $delyvaxConfig = $this->_delyvaxHelper->getDelyvaxConfig();
                    if ($delyvaxConfig['change_order_status']) {
                        $delyvaxOrderId = $data['orderId'];
                        $searchCriteria = $this->_searchCriteriaBuilder
                            ->addFilter('delyvax_order_id', $delyvaxOrderId, 'eq')->create();
                        $orderList = $this->_orderRepository->getList($searchCriteria)->getItems();
                        $order = reset($orderList);
                        if ($order) {
                            $statusCodesArr = $this->getStatusCodesWithValues();
                            $delyvaxStatusCode = $data['statusCode'];
                            if (array_key_exists($delyvaxStatusCode, $statusCodesArr)) {
                                $status = $statusCodesArr[$delyvaxStatusCode]['status'];
                                if ($order->getStatus() != $status) {
                                    $order->setDelyvaxOrderStatus($status);
                                    $order->addCommentToStatusHistory($statusCodesArr[$delyvaxStatusCode]['status_desc'], $status, true);
                                    $this->_orderResourceModel->save($order);
                                }
                            }
                        }
                    }
                }
            }
        }

    }

    /**
     * @return array
     */
    private function getStatusCodesWithValues(): array
    {
        /* '<<statusCode>>' => ['<<order-status>>', '<<status-description>>'] */
        return [
            '200' => ['status' => 'dx-courier-accepted', 'status_desc' => 'Order status changed to Courier accepted.'],
            '400' => ['status' => 'dx-start-collecting', 'status_desc' => 'Order status changed to Pending pick up.'],
            '475' => ['status' => 'dx-failed-collection', 'status_desc' => 'Order status changed to Pick up failed.'],
            '500' => ['status' => 'dx-collected', 'status_desc' => 'Order status changed to Pick up complete.'],
            '600' => ['status' => 'dx-start-delivery', 'status_desc' => 'Order status changed to On the way for delivery.'],
            '650' => ['status' => 'dx-failed-delivery', 'status_desc' => 'Order status changed to Delivery failed.'],
            '700' => ['status' => 'complete', 'status_desc' => 'Order status changed to Completed'],
            '900' => ['status' => 'canceled', 'status_desc' => 'Order status changed to Cancelled.'],
            '1000' => ['status' => 'complete', 'status_desc' => 'Order status changed to Completed']
        ];
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
