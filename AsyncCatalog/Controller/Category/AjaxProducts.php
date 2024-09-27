<?php

namespace Popoyan\AsyncCatalog\Controller\Category;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;


class AjaxProducts extends Action
{

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ImageHelper $imageHelper
     * @return void
     */
    public function __construct(
        private Context                     $context,
        private JsonFactory                 $resultJsonFactory,
        private CategoryRepositoryInterface $categoryRepository,
        private ProductCollectionFactory    $productCollectionFactory,
        private ImageHelper                 $imageHelper
    )
    {

        parent::__construct($context);
    }


    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $categoryId = $this->getRequest()->getParam('category_id');
        $category = $this->categoryRepository->get($categoryId);


        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'price', 'image'])
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
