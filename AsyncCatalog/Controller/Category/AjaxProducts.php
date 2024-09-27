<?php

namespace Popoyan\AsyncCatalog\Controller\Category;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Helper\Image as ImageHelper;

class AjaxProducts extends Action
{
    protected $resultJsonFactory;
    protected $categoryRepository;
    protected $productCollectionFactory;
    protected $imageHelper;

    public function __construct(
        Context                     $context,
        JsonFactory                 $resultJsonFactory,
        CategoryRepositoryInterface $categoryRepository,
        ProductCollectionFactory    $productCollectionFactory,
        ImageHelper                 $imageHelper
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->categoryRepository = $categoryRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->imageHelper = $imageHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $categoryId = $this->getRequest()->getParam('category_id');
        $category = $this->categoryRepository->get($categoryId);


        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'price', 'image']) // Asegurarse de seleccionar los atributos
            ->addCategoryFilter($category);

        $products = [];
        foreach ($productCollection as $product) {
            $products[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getFinalPrice(),
                'image' => $this->imageHelper->init($product, 'product_base_image')->getUrl()
            ];
        }

        return $result->setData($products);
    }
}
