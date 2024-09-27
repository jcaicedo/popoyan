<?php
declare(strict_types=1);

namespace Popoyan\InventorySync\Cron;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class SyncProducts
{
    const XML_PATH_API_URL = 'inventorysync/general/api_url';
    const XML_PATH_ENABLE_SYNC = 'inventorysync/general/enable_sync';

    const DEFAULT_CATEGORY_ID = 2;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param Client $client
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param SourceItemInterfaceFactory $sourceItemInterfaceFactory
     * @param ProductFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $categoryCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param State $state
     * @param ScopeConfigInterface $scopeConfig
     * @return void
     */
    public function __construct(
        private LoggerInterface                 $logger,
        private Client                          $client,
        private SourceItemsSaveInterface        $sourceItemsSave,
        private SourceItemInterfaceFactory      $sourceItemInterfaceFactory,
        private ProductFactory                  $productFactory,
        private ProductRepositoryInterface      $productRepository,
        private CategoryRepositoryInterface     $categoryRepository,
        private CategoryLinkManagementInterface $categoryLinkManagement,
        private CategoryFactory                 $categoryFactory,
        private CollectionFactory               $categoryCollectionFactory,
        private StoreManagerInterface           $storeManager,
        private State                           $state,
        private ScopeConfigInterface            $scopeConfig,
        private IndexerRegistry                 $indexerRegistry
    )
    {
    }

    /**
     * Executes the synchronization process.
     *
     * @return void
     */
    public function execute(): void
    {
        $apiUrl = $this->getApiUrl();

        if (!$this->isSyncEnabled()) {
            $this->logger->info('Sync is disabled.');
            return;
        }

        if (!$apiUrl) {
            $this->logger->info('Url API does not exist');
            return;
        }

        try {
            $this->state->setAreaCode(Area::AREA_CRONTAB);
            $response = $this->fetchResponse($apiUrl);
            $inventoryData = $this->decodeInventoryData($response);

            $this->processProducts($inventoryData['products']);
            $this->reindex();
            $this->logger->info('Products synced successfully');
        } catch (\Exception $exception) {
            $this->logger->error('Error syncing products: ' . $exception->getMessage());
        }
    }

    /**
     * Fetches a response from the given API URL using a GET request.
     *
     * @param string $apiUrl The URL of the API to fetch the response from.
     * @return \Psr\Http\Message\ResponseInterface The response from the API request.
     * @throws GuzzleException
     */
    private function fetchResponse(string $apiUrl): \Psr\Http\Message\ResponseInterface
    {
        return $this->client->request('GET', $apiUrl);
    }

    /**
     * Decodes inventory data from the given response.
     *
     * @param mixed $response The response containing the inventory data to decode.
     * @return array The decoded inventory data.
     */
    private function decodeInventoryData($response): array
    {
        $this->validateResponse($response);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Validates the response object to ensure the API request was successful.
     *
     * @param object $response The response object to verify.
     * @return void
     * @throws \Exception If the response status code is not 200.
     */
    private function validateResponse($response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to fetch products.");
        }
    }

    /**
     * Processes an array of products by initializing, updating, and saving each product,
     * and then creates and saves corresponding source items.
     *
     * @param array $products Array of products to process.
     * @return void
     */
    private function processProducts(array $products): void
    {
        $sourceItemsArray = [];
        foreach ($products as $product) {
            try {
                $productEntity = $this->initializeProduct($product);
                $this->updateProduct($productEntity, $product);
                $this->saveProduct($productEntity, $product);
                $sourceItemsArray[] = $this->createSourceItem($product);
            } catch (NoSuchEntityException $e) {
                $this->logger->warning("Product with SKU {$product['sku']} not found.");
            } catch (\Exception $e) {
                $this->logger->error("Error processing product with SKU {$product['sku']}: " . $e->getMessage());
            }
        }
        $this->saveSourceItems($sourceItemsArray);
    }

    /**
     * Initialize a product by SKU. If the product already exists, it will be returned.
     * Otherwise, a new product entity will be created and returned.
     *
     * @param array $product The product data containing 'sku' and 'title'.
     * @return ProductInterface The existing or newly created product entity.
     */
    private function initializeProduct(array $product): ProductInterface
    {
        try {
            return $this->productRepository->get($product['sku']);
        } catch (NoSuchEntityException $e) {
            $productEntity = $this->productFactory->create();
            $productEntity->setSku($product['sku']);
            $productEntity->setName($product['title']);
            $productEntity->setAttributeSetId(4);
            $productEntity->setStatus(1);
            $productEntity->setVisibility(4);
            $productEntity->setTypeId('simple');
            return $productEntity;
        }
    }

    /**
     * Updates the given product entity with provided product data.
     *
     * @param object $productEntity The product entity to be updated.
     * @param array $product An associative array containing product data such as 'price' and 'category'.
     * @return void
     */
    private function updateProduct($productEntity, array $product): void
    {
        $productEntity->setPrice($product['price']);

        if (isset($product['category'])) {
            try {

                $category = $this->assignCategoryToProduct($product['category']);

                $productEntity->setCategoryIds([(int)$category->getId()]);
            } catch (\Exception $e) {
                $this->logger->error("Error processing category with Name {$product['category']}: " . $e->getMessage());
            }
        }
    }

    /**
     * Saves the given product entity to the repository.
     *
     * @param object $productEntity The product entity to be saved.
     * @param array $product An associative array containing product data such as 'sku'.
     * @return void
     */
    private function saveProduct($productEntity, array $product): void
    {
        try {
            $this->productRepository->save($productEntity);
            $this->logger->info(($productEntity->getId() ? "Product updated: {$product['sku']}" : "Product created: {$product['sku']}"));
        } catch (\Exception $e) {
            $this->logger->error("Error saving product: {$product['sku']} -> " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Creates a source item for the given product.
     *
     * @param array $product An associative array containing product data such as 'sku' and 'stock'.
     * @return object The created source item.
     */
    private function createSourceItem(array $product): object
    {
        $sourceItem = $this->sourceItemInterfaceFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setSku($product['sku']);
        $sourceItem->setQuantity($product['stock']);
        $sourceItem->setStatus($product['stock'] > 0 ? 1 : 0);
        return $sourceItem;
    }

    /**
     * Saves the provided array of source items.
     *
     * @param array $sourceItemsArray Array of source items to be saved.
     * @return void
     */
    private function saveSourceItems(array $sourceItemsArray): void
    {
        if (!empty($sourceItemsArray)) {
            try {
                $this->sourceItemsSave->execute($sourceItemsArray);
            } catch (\Exception $e) {
                $this->logger->error("Error saving source items: " . $e->getMessage());
            }
        }
    }

    /**
     * Retrieves the API URL from the configuration.
     *
     * @return string|null The API URL if configured, or null if not set.
     */
    private function getApiUrl(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_API_URL);
    }

    /**
     * Checks if the synchronization feature is enabled.
     *
     * @return bool True if synchronization is enabled, false otherwise.
     */
    private function isSyncEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE_SYNC);
    }

    /**
     * Assigns a category to a product based on the provided category name.
     * If the category does not exist, it will create a new category.
     *
     * @param string $categoryName Name of the category to assign.
     * @return \Magento\Catalog\Model\Category The assigned or newly created category.
     */
    private function assignCategoryToProduct(string $categoryName): \Magento\Catalog\Model\Category
    {
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('name', $categoryName)
            ->getFirstItem();

        if (!$category->getId()) {

            $category = $this->categoryFactory->create();
            $category->setName($categoryName);
            $category->setIsActive(true);
            $category->setParentId(self::DEFAULT_CATEGORY_ID);
            $category->setIsAnchor(true);
            $category->setIncludeInMenu(true);
            $category->setStoreId($this->storeManager->getStore()->getId());
            $category->setUrlKey(strtolower(str_replace(' ', '-', $categoryName)));
            $category->setMetaTitle($categoryName);
            $category->setMetaDescription($categoryName);
            $this->categoryRepository->save($category);
        }
        return $category;
    }

    private function reindex(): void
    {
        $indexerIds = [
            'cataloginventory_stock',
            'catalog_category_product',
            'catalog_product_category',
            'catalogrule_rule',
            'catalog_product_attribute',
            'catalogsearch_fulltext',
            'inventory',
            'inventory_source_item'
        ];

        foreach ($indexerIds as $indexerId) {
            try {
                $indexer = $this->indexerRegistry->get($indexerId);
                if (!$indexer->isScheduled()) {
                    $indexer->reindexAll();
                    $this->logger->info("Reindexed: {$indexerId}");
                }
            } catch (\Exception $e) {
                $this->logger->error("Error reindexing {$indexerId}: " . $e->getMessage());
            }
        }
    }
}
