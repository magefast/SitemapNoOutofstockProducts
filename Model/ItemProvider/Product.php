<?php
/**
 * @author magefast@gmail.com www.magefast.com
 */

declare(strict_types=1);

namespace Magefast\SitemapNoOutofstockProducts\Model\ItemProvider;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Sitemap\Model\ItemProvider\ConfigReaderInterface;
use Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;

class Product extends \Magento\Sitemap\Model\ItemProvider\Product
{

    /**
     * Product factory
     *
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * Sitemap item factory
     *
     * @var SitemapItemInterfaceFactory
     */
    private $itemFactory;

    /**
     * Config reader
     *
     * @var ConfigReaderInterface
     */
    private $configReader;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * ProductSitemapItemResolver constructor.
     *
     * @param ConfigReaderInterface $configReader
     * @param ProductFactory $productFactory
     * @param SitemapItemInterfaceFactory $itemFactory
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ConfigReaderInterface       $configReader,
        ProductFactory              $productFactory,
        SitemapItemInterfaceFactory $itemFactory,
        StockRegistryInterface      $stockRegistry
    )
    {
        parent::__construct($configReader, $productFactory, $itemFactory);

        $this->productFactory = $productFactory;
        $this->itemFactory = $itemFactory;
        $this->configReader = $configReader;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($storeId)
    {
        $collection = $this->productFactory->create();
        $collection = $collection->getCollection($storeId);

        foreach ($collection as $key => $value) {
            $stock = $this->stockRegistry->getStockItem($value->getId());
            if ($stock->getIsInStock() !== true) {
                unset($collection[$key]);
            }
        }

        $items = array_map(function ($item) use ($storeId) {
            return $this->itemFactory->create([
                'url' => $item->getUrl(),
                'updatedAt' => $item->getUpdatedAt(),
                'images' => $item->getImages(),
                'priority' => $this->configReader->getPriority($storeId),
                'changeFrequency' => $this->configReader->getChangeFrequency($storeId),
            ]);
        }, $collection);

        return $items;
    }
}