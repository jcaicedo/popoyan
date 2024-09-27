<?php
declare(strict_types=1);

namespace Popoyan\InventorySync\Cron;

use GuzzleHttp\Client;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class SyncProducts
{
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
        private State                           $state

    )
    {
    }

    public function execute(): void
    {
        $apiUrl = "https://dummyjson.com/products";
        try {
            $this->state->setAreaCode(Area::AREA_CRONTAB);
            $response = $this->client->request('GET', $apiUrl);
            $this->validateResponse($response);

            $inventoryData = json_decode($response->getBody()->getContents(), true);
            $this->processProducts($inventoryData['products']);

            $this->logger->info('Products synced successfully');
        } catch (\Exception $exception) {
            $this->logger->error('Error syncing products: ' . $exception->getMessage());
        }
    }

    private function validateResponse($response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Failed to fetch products");
        }
    }

    private function processProducts(array $productsData): void
    {
        $sourceItemsArray = [];
        foreach ($productsData as $productData) {
            try {
                $product = $this->initializeProduct($productData);
                $this->updateProduct($product, $productData);
                $this->saveProduct($product, $productData);
                $sourceItemsArray[] = $this->createSourceItem($productData);
            } catch (NoSuchEntityException $e) {
                $this->logger->warning("Product with SKU {$productData['sku']} not found.");
            } catch (\Exception $e) {
                $this->logger->error("Error processing product with SKU {$productData['sku']}: " . $e->getMessage());
            }
        }
        $this->saveSourceItems($sourceItemsArray);
    }

    private function initializeProduct(array $productData)
    {
        try {
            return $this->productRepository->get($productData['sku']);
        } catch (NoSuchEntityException $e) {
            $product = $this->productFactory->create();
            $product->setSku($productData['sku']);
            $product->setName($productData['title']);
            $product->setAttributeSetId(4);
            $product->setStatus(1);
            $product->setVisibility(4);
            $product->setTypeId('simple');


            return $product;
        }
    }

    private function assignCategoryToProduct(string $categoryName): DataObject|\Magento\Catalog\Model\Category
    {
        $parentCategoryId = 2;
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('name', $categoryName)
            ->getFirstItem();
        if (!$category->getId()) {
            $category = $this->categoryFactory->create();
            $category->setName($categoryName);
            $category->setIsActive(true); // Activar la categoría
            $category->setParentId($parentCategoryId); // Hija de Default Category
            $category->setIsAnchor(true); // Habilitar como anchor (opcional)
            $category->setIncludeInMenu(true); // Incluir en el menú
            $category->setStoreId($this->storeManager->getStore()->getId());
            $category->setUrlKey(strtolower(str_replace(' ', '-', $categoryName))); // Crear un URL key
            $category->setMetaTitle($categoryName);
            $category->setMetaDescription($categoryName);

            $this->categoryRepository->save($category);
        }

        return $category;

    }

    private function updateProduct($product, array $productData): void
    {
        $product->setPrice($productData['price']);

        if (isset($productData['category'])) {
            try {
                $category = $this->assignCategoryToProduct($productData['category']);
                $product->setCategoryIds([(int)$category->getId()]);
            } catch (\Exception $e) {
                $this->logger->error("Error processing category with Name {$productData['category']}: " . $e->getMessage());
            }

        }
    }

    private function saveProduct($product, array $productData): void
    {
        try {
            $this->productRepository->save($product);
            $this->logger->info(($product->getId() ? "Product updated: {$product->getSku()}" : "Product created: {$product->getSku()}"));
        } catch (\Exception $e) {
            $this->logger->error("Error saving product: {$product->getSku()} -> " . $e->getMessage());
            throw $e;
        }
    }

    private function createSourceItem(array $productData)
    {
        $sourceItem = $this->sourceItemInterfaceFactory->create();
        $sourceItem->setSourceCode('default');
        $sourceItem->setSku($productData['sku']);
        $sourceItem->setQuantity($productData['stock']);
        $sourceItem->setStatus($productData['stock'] > 0 ? 1 : 0);
        return $sourceItem;
    }

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


}
