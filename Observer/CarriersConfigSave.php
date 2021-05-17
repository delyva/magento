<?php

namespace Delyvax\Shipment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CarriersConfigSave implements ObserverInterface
{
    /**
     * @var DelyvaxHelper
     */
    protected $_delyvaxHelper;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     *  @var WriterInterface
     */
    protected $_configWriter;

    /**
     * @param DelyvaxHelper $delyvaxHelper
     * @param Logger $logger
     * @param WriterInterface $configWriter
     */
    public function __construct(
        DelyvaxHelper $delyvaxHelper,
        Logger $logger,
        WriterInterface $configWriter
    ) {
        $this->_delyvaxHelper = $delyvaxHelper;
        $this->_logger = $logger;
        $this->_configWriter = $configWriter;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $config = $this->_delyvaxHelper->getDelyvaxWebhookConfig();
        if ($config['delyvax_api_webhook_enable']) {
            if (!$config['delyvax_api_webhook_order_created_id']) {
                // $this->_logger->info('delyvax_api_webhook_order_created_id not exist');
                $result = $this->_delyvaxHelper->postCreateWebhook('order.created');
                if (array_key_exists('id', $result[DelyvaxHelper::RESPONSE])) {
                    $this->_configWriter->save(DelyvaxHelper::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_created_id', $result[DelyvaxHelper::RESPONSE]['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                }
            }
            if (!$config['delyvax_api_webhook_order_failed_id']) {
                // $this->_logger->info('delyvax_api_webhook_order_failed_id not exist');
                $result = $this->_delyvaxHelper->postCreateWebhook('order.failed');
                if (array_key_exists('id', $result[DelyvaxHelper::RESPONSE])) {
                    $this->_configWriter->save(DelyvaxHelper::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_failed_id', $result[DelyvaxHelper::RESPONSE]['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                }
            }
            if (!$config['delyvax_api_webhook_order_updated_id']) {
                // $this->_logger->info('delyvax_api_webhook_order_updated_id not exist');
                $result = $this->_delyvaxHelper->postCreateWebhook('order.updated');
                if (array_key_exists('id', $result[DelyvaxHelper::RESPONSE])) {
                    $this->_configWriter->save(DelyvaxHelper::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_updated_id', $result[DelyvaxHelper::RESPONSE]['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                }
            }
            if (!$config['delyvax_api_webhook_order_tracking_update_id']) {
                // $this->_logger->info('delyvax_api_webhook_order_tracking_update_id not exist');
                $result = $this->_delyvaxHelper->postCreateWebhook('order_tracking.update');
                if (array_key_exists('id', $result[DelyvaxHelper::RESPONSE])) {
                    $this->_configWriter->save(DelyvaxHelper::DELYVAX_CREDENTIALS_PATH . 'delyvax_api_webhook_order_tracking_update_id', $result[DelyvaxHelper::RESPONSE]['id'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                }
            }
        }
    }
}
