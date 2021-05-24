<?php

namespace Delyvax\Shipment\Model\Rewrite\ResourceModel\Order\Handler;

use Magento\Sales\Model\Order;

class State extends \Magento\Sales\Model\ResourceModel\Order\Handler\State
{
    public function check(Order $order)
    {
        $currentState = $order->getState();
        if ($currentState == Order::STATE_NEW && $order->getIsInProcess()) {
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
            $currentState = Order::STATE_PROCESSING;
        }
        /*commenting the code below to stop Magento from changing order status to complete/closed on Shipment creation*/
        /*if (!$order->isCanceled() && !$order->canUnhold() && !$order->canInvoice()) {
            if (in_array($currentState, [Order::STATE_PROCESSING, Order::STATE_COMPLETE])
                && !$order->canCreditmemo()
                && !$order->canShip()
            ) {
                $order->setState(Order::STATE_CLOSED)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
            } elseif ($currentState === Order::STATE_PROCESSING && !$order->canShip()) {
                $order->setState(Order::STATE_COMPLETE)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
            }
        }*/
        return $this;
    }
}

