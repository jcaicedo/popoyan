<?php

namespace Popoyan\CustomGraphQL\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class CustomProductInfo implements ResolverInterface
{

    /**
     * Constructor for initializing dependencies.
     *
     * @param ProductRepositoryInterface $productRepository Repository interface for handling products.
     * @param StockRegistryInterface $stockRegistry Interface for managing stock registry.
     * @param StoreManagerInterface $storeManager Interface for managing store details.
     * @param ResourceConnection $resourceConnection Connection resource instance.
     * @param SearchCriteriaBuilder $searchCriteriaBuilder Builder for search criteria.
     * @param CollectionFactory $productCollectionFactory Factory for creating product collections.
     *
     * @return void
     */
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private StockRegistryInterface     $stockRegistry,
        private StoreManagerInterface      $storeManager,
        private ResourceConnection         $resourceConnection,
        private SearchCriteriaBuilder      $searchCriteriaBuilder,
        private CollectionFactory          $productCollectionFactory
    )
    {
    }

    /**
     * @param $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @throws GraphQlAuthorizationException
     * @throws NoSuchEntityException
     */
    public function resolve(
        $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array
    {
        if (!$context->getUserId()) {
            throw new GraphQlAuthorizationException(__('User is not authorized'));
        }

        $skus = $args['skus'] ?? [];
        $pageSize = $args['pageSize'] ?? 20;
        $currentPage = $args['currentPage'] ?? 1;
        $storeId = $this->storeManager->getStore()->getId();

        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'price'])
            ->addAttributeToFilter('sku', ['in' => $skus])
            ->setCurPage($currentPage)
            ->setPageSize($pageSize);

        $totalCount = $collection->getSize();

        $products = [];
        foreach ($collection as $product) {
            try {
                $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
                $products[] = [
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'qty' => $stockItem->getQty()
                ];
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }

        return [
            'items' => $products,
            'total_count' => $totalCount,
            'page_info' => [
                'page_size' => $pageSize,
                'current_page' => $currentPage
            ]
        ];
    }
}
