<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Update default stock status for given skus.
 */
class UpdateDefaultStockStatus
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param ResourceConnection $resource
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        ResourceConnection $resource,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->resource = $resource;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Update default stock status for given skus.
     *
     * @param array $dataForUpdate
     * @param string $connectionName
     */
    public function execute(array $dataForUpdate, string $connectionName): void
    {
        $connection = $this->resource->getConnection($connectionName);
        $tableName = $connection->getTableName('cataloginventory_stock_status');
        $productIds = $this->getProductIdsBySkus->execute(array_keys($dataForUpdate));
        foreach ($dataForUpdate as $sku => $isSalable) {
            $connection->update(
                $tableName,
                ['stock_status' => $isSalable],
                ['product_id = ?' => (int) $productIds[$sku]]
            );
        }
    }
}
