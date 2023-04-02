<?php
namespace Delyvax\Shipment\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Eav\Setup\EavSetupFactory;
use Delyvax\Shipment\Helper\Data as DelyvaxHelper;

class UpgradeData implements UpgradeDataInterface
{
    const SETUP = 'setup';
    const LENGTH = 'length';
    const WIDTH = 'width';
    const HEIGHT = 'height';

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * UpgradeData constructor.
     * @param EavSetupFactory $eavSetupFactory
     * @throws LocalizedException
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * @var $eavSetup EavSetup
         */
        $eavSetup = $this->eavSetupFactory->create([self::SETUP => $setup]);

        if (version_compare($context->getVersion(), "2.0.0", "<")) {
            $setup->startSetup();
            $data = [
                ['status' => 'dx-preparing', 'label' => 'Preparing'],
                ['status' => 'dx-ready-to-collect', 'label' => 'Ready to collect'],
                ['status' => 'dx-courier-accepted', 'label' => 'Courier accepted'],
                ['status' => 'dx-start-collecting', 'label' => 'Pending pick up'],
                ['status' => 'dx-collected', 'label' => 'Pick up complete'],
                ['status' => 'dx-failed-collection', 'label' => 'Pick up failed'],
                ['status' => 'dx-start-delivery', 'label' => 'On the way for delivery'],
                ['status' => 'dx-failed-delivery', 'label' => 'Delivery failed'],
                ['status' => 'dx-request-refund', 'label' => 'Request refund']
            ];
            $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);
            $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default','visible_on_front'],
                [
                    ['dx-preparing','processing', '0', '1'],
                    ['dx-ready-to-collect','processing', '0', '1'],
                    ['dx-courier-accepted','processing', '0', '1'],
                    ['dx-start-collecting','processing', '0', '1'],
                    ['dx-collected','processing', '0', '1'],
                    ['dx-failed-collection','processing', '0', '1'],
                    ['dx-start-delivery','processing', '0', '1'],
                    ['dx-failed-delivery','processing', '0', '1'],
                    ['dx-request-refund','processing', '0', '1']
                ]
            );
            $setup->endSetup();
        }


        if (version_compare($context->getVersion(), "2.1.0", "<")) {
            if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, self::LENGTH)) {
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    self::LENGTH,
                    [
                        'type' => 'int',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Length (cm)',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => '100',
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to' => ''
                    ]
                );
            }

            if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, self::WIDTH)) {
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    self::WIDTH,
                    [
                        'type' => 'int',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Width (cm)',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => '100',
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to' => ''
                    ]
                );
            }

            if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, self::HEIGHT)) {
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    self::HEIGHT,
                    [
                        'type' => 'int',
                        'backend' => '',
                        'frontend' => '',
                        'label' => 'Height (cm)',
                        'input' => 'text',
                        'class' => '',
                        'source' => '',
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => false,
                        'default' => '100',
                        'searchable' => false,
                        'filterable' => false,
                        'comparable' => false,
                        'visible_on_front' => false,
                        'used_in_product_listing' => true,
                        'unique' => false,
                        'apply_to' => ''
                    ]
                );
            }
        }

        if (version_compare($context->getVersion(), "2.2.0", "<")) {
            if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, DelyvaxHelper::ATTR_IS_ITEM_FRESH)) {
                $eavSetup->addAttribute(
                    \Magento\Catalog\Model\Product::ENTITY,
                    DelyvaxHelper::ATTR_IS_ITEM_FRESH,
                    [
                        'type' => 'int',
                        'frontend' => '',
                        'label' => 'Is Item Fresh',
                        'input' => 'boolean',
                        'backend' => \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
                        'source' => \Magento\Catalog\Model\Product\Attribute\Source\Boolean::class,
                        'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                        'visible' => true,
                        'required' => false,
                        'user_defined' => true,
                        'default' => 0,
                        'visible_on_front' => false,
                        'unique' => false,
                        'is_used_in_grid' => true,
                        'sort_order' => 49
                    ]
                );
            }
            $attributeSetId = $eavSetup->getDefaultAttributeSetId(\Magento\Catalog\Model\Product::ENTITY);

            $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
            $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);
            foreach ($attributeSetIds as $attributeSetId) {
                if ($attributeSetId) {
                    $eavSetup->addAttributeToGroup(
                        \Magento\Catalog\Model\Product::ENTITY,
                        $attributeSetId,
                        'Default',
                        DelyvaxHelper::ATTR_IS_ITEM_FRESH,
                        99
                    );
                }
            }
        }

    }
}
