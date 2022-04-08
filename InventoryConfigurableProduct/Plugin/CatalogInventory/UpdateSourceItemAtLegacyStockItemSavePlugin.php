<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory;

use Magento\Catalog\Model\ResourceModel\GetProductTypeById;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\Framework\Model\AbstractModel as StockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Class provides after Plugin on Magento\CatalogInventory\Model\ResourceModel\Stock\Item::save
 * to update legacy stock status for configurable product
 */
class UpdateSourceItemAtLegacyStockItemSavePlugin
{
    /**
     * @var GetProductTypeById
     */
    private $getProductTypeById;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param GetProductTypeById $getProductTypeById
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param Configurable $configurableType
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        GetProductTypeById $getProductTypeById,
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        Configurable $configurableType,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->getProductTypeById = $getProductTypeById;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->configurableType = $configurableType;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Update source item for legacy stock of a configurable product
     *
     * @param ItemResourceModel $subject
     * @param ItemResourceModel $result
     * @param StockItem $stockItem
     * @return void
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(ItemResourceModel $subject, ItemResourceModel $result, StockItem $stockItem): void
    {
        if ($stockItem->getIsInStock() &&
            $this->getProductTypeById->execute($stockItem->getProductId()) === Configurable::TYPE_CODE
        ) {
            if ($stockItem->getStockStatusChangedAuto() ||
                ($stockItem->getOrigData('is_in_stock') == Stock::STOCK_OUT_OF_STOCK &&
                    $this->hasChildrenInStock($stockItem->getProductId()))
            ) {
                $productSku = $this->getSkusByProductIds
                    ->execute([$stockItem->getProductId()])[$stockItem->getProductId()];
                $this->setDataToLegacyStockStatus->execute(
                    $productSku,
                    (float) $stockItem->getQty(),
                    Stock::STOCK_IN_STOCK
                );
            }
        }
    }

    /**
     * Checks if configurable has salable options
     *
     * @param int $productId
     * @return bool
     */
    private function hasChildrenInStock(int $productId): bool
    {
        $childrenIds = $this->configurableType->getChildrenIds($productId);
        if (empty($childrenIds)) {
            return false;
        }
        $skus = $this->getSkusByProductIds->execute(array_shift($childrenIds));
        $areSalableResults = $this->areProductsSalable->execute($skus, Stock::DEFAULT_STOCK_ID);
        foreach ($areSalableResults as $productSalable) {
            if ($productSalable->isSalable() === true) {
                return true;
            }
        }

        return false;
    }
}
