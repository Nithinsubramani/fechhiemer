<?php

namespace I95Dev\CatalogSetup\Block\Slider;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogWidget\Model\Rule;
use Magento\Customer\Model\Session;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Reports\Block\Product\Widget\Viewed\Proxy;
use Magento\Reports\Model\Event\TypeFactory;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Widget\Block\BlockInterface;
use Magento\Widget\Helper\Conditions;
use WeltPixel\MobileDetect\Helper\Data;
use WeltPixel\OwlCarouselSlider\Helper\Custom;
use WeltPixel\OwlCarouselSlider\Model\Config\Source\SortOrder;
use Magento\Customer\Model\SessionFactory;

class Products extends AbstractProduct implements BlockInterface
{
    const TIME_FORMAT = 'Y-m-d H:i:s';
    /**
     * Core registry.
     *
     * @var Registry
     */
    protected $_coreRegistry;
    protected $_helperProducts;
    protected $_helperCustom;
    protected $_productType;
    protected $_sliderConfiguration;

    protected $_currentProduct;
    /**
     * Products visibility
     * @var TypeFactory
     */
    protected $_catalogProductVisibility;

    protected $_productCollectionFactory;
    protected $_reportsCollectionFactory;
    protected $_viewProductsBlock;
    protected $_categoryFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var Data
     */
    private $_mobileHelperData;

    /**
     * @var SqlBuilder
     */
    protected $sqlBuilder;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var Conditions
     */
    protected $conditionsHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var AttributeRepositoryInterface
     */
    protected $attributeRepository;

    protected $_productsFactory;
    protected $_orderItemFactory;
    protected $_orderCollectionFactory;
    protected $_itemCollectionFactory;
    protected $_customerSession;

    /**
     * Products constructor.
     * @param Context $context
     * @param \WeltPixel\OwlCarouselSlider\Helper\Products $helperProducts
     * @param Custom $helperCustom
     * @param Visibility $catalogProductVisibility
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productsCollectionFactory
     * @param \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportsCollectionFactory
     * @param Proxy $viewedProductsBlock
     * @param CategoryFactory $categoryFactory
     * @param Data $mobileHelperData
     * @param SqlBuilder $sqlBuilder
     * @param Rule $rule
     * @param Conditions $conditionsHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param AttributeRepositoryInterface $attributeRepository
     * @param array $data
     * phpcs:disable
     */
    public function __construct(
        Context $context,
        \WeltPixel\OwlCarouselSlider\Helper\Products $helperProducts,
        Custom $helperCustom,
        Visibility $catalogProductVisibility,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productsCollectionFactory,
        \Magento\Reports\Model\ResourceModel\Product\CollectionFactory $reportsCollectionFactory,
        Proxy $viewedProductsBlock,
        CategoryFactory $categoryFactory,
        Data $mobileHelperData,
        SqlBuilder $sqlBuilder,
        Rule $rule,
        Conditions $conditionsHelper,
        CategoryRepositoryInterface $categoryRepository,
        AttributeRepositoryInterface $attributeRepository,
        ProductFactory $productsFactory,
        ItemFactory $orderItemFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        CollectionFactory $itemCollectionFactory,
        Session $customerSession,
        array $data = []
    )
    {
        $this->_coreRegistry = $context->getRegistry();
        $this->_helperCustom = $helperCustom;
        $this->_helperProducts = $helperProducts;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_productCollectionFactory = $productsCollectionFactory;
        $this->_reportsCollectionFactory = $reportsCollectionFactory;
        $this->_viewProductsBlock = $viewedProductsBlock;
        $this->_categoryFactory = $categoryFactory;
        $this->_mobileHelperData = $mobileHelperData;
        $this->sqlBuilder = $sqlBuilder;
        $this->rule = $rule;
        $this->conditionsHelper = $conditionsHelper;
        $this->categoryRepository = $categoryRepository;
        $this->attributeRepository = $attributeRepository;

        $this->setTemplate('sliders/products.phtml');
        // phpcs:disable
        if (is_null($this->_currentProduct)) {
            $this->_currentProduct = $this->_coreRegistry->registry('current_product');
        }

        $this->_localeDate = $context->getLocaleDate();
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_productsFactory = $productsFactory;
        $this->_orderItemFactory = $orderItemFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_itemCollectionFactory = $itemCollectionFactory;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();

        $this->addData([
            'cache_lifetime' => false,
            'cache_tags' => [Product::CACHE_TAG,
            ],]);
    }

    /**
     * Get key pieces for caching block content
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return [
            'WELTPIXEL_PRODUCTS_LIST_WIDGET',
            $this->_storeManager->getStore()->getId(),
            $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
            $this->_design->getDesignTheme()->getId(),
            $this->getData('products_type'),
            json_encode($this->getRequest()->getParams())
        ];
    }

    /**
     * Retrieve the product collection based on product type.
     *
     * @return array|Collection
     */
    public function getProductCollection()
    {
        $productsType = $this->getData('products_type');
        $productsInCates = $this->getData('products_in_categories');
        $categories = isset($productsInCates) ? explode(",", $productsInCates) : [];

        switch ($productsType) {
            case 'new_products':
                $productCollection = $this->_getNewProductCollection(
                    $this->_productCollectionFactory->create(),
                    $categories
                );
                break;
            case 'bestsell_products':
                $productCollection = $this->_getBestsellProductCollection($this->_productCollectionFactory->create());
                break;
            case 'sell_products':
                $productCollection = $this->_getSellProductCollection($this->_productCollectionFactory->create());
                break;
            case 'recently_viewed':
                $productCollection = $this->_getRecentlyViewedCollection($this->_productCollectionFactory->create());
                break;
            case 'category_products':
                $categoryId = $this->_getCategoryIdFrom($this->getData('category'));
                $productCollection = $this->_getCustomCategoryCollection($categoryId);
                break;
            case 'related_products':
                $productCollection = $this->getProductCollectionRelated();
                break;
            case 'upsell_products':
                $productCollection = $this->getProductCollectionUpSell();
                break;
            case 'crosssell_products':
                $productCollection = $this->getProductCollectionCrossSell();
                break;
            case 'conditions_based_products':
                $productCollection = $this->_getProductCollectionConditionBased(
                    $this->_productCollectionFactory->create()
                );
                break;
            case 'recently_purchased':
                $customerId = $this->getCustomerId();
                $productCollection = $this->_getRecentPurchasedCollection(
                    $this->_productCollectionFactory->create(),
                    $customerId
                );
                break;
            default:
                $productCollection = [];
        }

        return $productCollection;
    }

    /**
     * Retrieve the Slider settings.
     *
     * @return array
     */
    public function getSliderConfiguration()
    {
        $productsType = $this->getData('products_type');
        //  phpcs:disable
        if (is_null($this->_sliderConfiguration) && $this->_productType != $productsType) {
            $this->_productType = $productsType;
            $this->_sliderConfiguration = $this->_helperProducts->getSliderConfigOptions($productsType);
        }

        return $this->_sliderConfiguration;
    }

    /**
     * Retrieve the Slider Breakpoint settings.
     *
     * @return array
     */
    public function getBreakpointConfiguration()
    {
        return $this->_helperCustom->getBreakpointConfiguration();
    }

    /**
     * return customerId.
     *
     */

    public function getCustomerId()
    {
        $objectManager = ObjectManager::getInstance();
        $customerSession = $objectManager->get(SessionFactory::class)->create();
        return $customerSession->getCustomer()->getId();
    }

    /**
     * Get new slider products.
     *
     * @param Collection $_collection
     * @return Collection
     */
    protected function _getNewProductCollection($_collection, $cIds)
    {
        $limit = $this->_getProductLimit('new_products');
        $sortOrder = $this->_getSortOrder('new_products');
        $random = ($sortOrder == SortOrder::SORT_RANDOM);
        if (count($cIds) > 0) {
            $_collection->addCategoriesFilter(['in' => $cIds]);
        }
        if (!$limit || $limit == 0) {
            return [];
        }

        if ($random) {
            $allIds = $_collection->getAllIds();
            $randomIds = [];
            $maxKey = count($allIds) - 1;
            while (count($randomIds) <= count($allIds) - 1) {
                $randomKey = random_int(0, $maxKey);
                $randomIds[$randomKey] = $allIds[$randomKey];
            }

            $_collection->addIdFilter($randomIds);
        }
        $_collection = $this->_addProductAttributesAndPrices($_collection)
            ->addStoreFilter($this->getStoreId())->setCurPage(1);

        if ($limit && $limit > 0) {
            $_collection->setPageSize($limit);
        }

        return $_collection;
    }

    protected function _getRecentPurchasedCollection($collection, $customerId)
    {
        $limit = $this->_getProductLimit('recently_purchased');
        $random = $this->_getRandomSort('recently_purchased');

        if (!$limit || $limit == 0) {
            return [];
        }

        $ordercollection = $this->_orderCollectionFactory->create();
        $orderTable = $ordercollection->getTable('sales_order');
        $orderDatamodel = $this->_orderItemFactory->create()->getCollection();

        $select = $orderDatamodel->getSelect()
            ->joinLeft(
                ['sales_flat_order' => $orderTable],
                'main_table.order_id = sales_flat_order.entity_id',
                ['main_table.product_id']
            )
            ->group('main_table.product_id')
            ->order("main_table.created_at DESC")
            ->where("sales_flat_order.state = 'complete'")
            ->where("sales_flat_order.customer_id = $customerId")
            ->limit($limit);

        $_conn = $collection->getResource()->getConnection();
        $result = $_conn->fetchAll($select);

        $purchasedproducts = [];
        foreach ($result as $item) {
            $purchasedproducts[$item['product_id']] = $item['product_id'];
        }

        $_collection = $collection->addAttributeToFilter('entity_id', ['in' => array_keys($purchasedproducts)]);

        if ($random) {
            $allIds = $_collection->getAllIds();
            $randomIds = [];
            $maxKey = count($allIds) - 1;
            while (count($randomIds) <= count($allIds) - 1) {
               
                $randomKey = mt_rand(0, $maxKey); //NOSONAR 
                $randomIds[$randomKey] = $allIds[$randomKey];
            }
            $_collection->addIdFilter($randomIds);
        }

        $_collection = $this->_addProductAttributesAndPrices($_collection)
            ->addStoreFilter($this->getStoreId())->setCurPage(1);

        if ($limit && $limit > 0) {
            $_collection->setPageSize($limit);
        }
        return $_collection;
    }

    public function _preparePurchasedProductsCollection()
    {
        $orderItemcollection = $this->_itemCollectionFactory->create();
        $orderItemTable = $orderItemcollection->getTable('sales_order_item');
        $ordercollection = $this->_orderCollectionFactory->create();
        $orderTable = $ordercollection->getTable('sales_order');

        $_collection = $this->_productCollectionFactory->create()->getSelect()
            ->joinLeft(['order_item' => $orderItemTable], 'order_item.product_id = e.entity_id', [])
            ->joinLeft(['order' => $orderTable], 'order.entity_id = order_item.order_id', []);
        return $this->setCollection($_collection);
    }

    /**
     * Get best-sell slider products.
     *
     * @param $_collection
     * @return array|Collection
     */
    protected function _getBestsellProductCollection($_collection)
    {
        $limit = $this->_getProductLimit('bestsell_products');
        $sortOrder = $this->_getSortOrder('bestsell_products');
        $random = ($sortOrder == SortOrder::SORT_RANDOM);

        if (!$limit || $limit == 0) {
            return [];
        }

        $_collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

        if ($random) {
            $allIds = $_collection->getAllIds();
            $candidateIds = $_collection->getAllIds();
            $randomIds = [];
            $maxKey = count($candidateIds) - 1;
            while (count($randomIds) <= count($allIds) - 1) {
                $randomKey = random_int(0, $maxKey);
                $randomIds[$randomKey] = $candidateIds[$randomKey];
            }

            $_collection->addIdFilter($randomIds);
        } elseif ($sortOrder) {
            $this->_sortCollectionWithCustomOrder($_collection, $sortOrder);
        }

        $bestsellersTableName = 'sales_bestsellers_aggregated_yearly';

        /** Prepare filter by period */
        $storeId = $this->getStoreId();
        $currentDate = $this->_localeDate->date();
        $period = $this->_sliderConfiguration['period'];
        switch ($period) {
            case 'last_day':
                $yesterday = $this->_localeDate->date(strtotime('-1 day', $currentDate->getTimestamp()))
                    ->format('Y-m-d');
                $periodFilter['from'] = $yesterday;
                $periodFilter['to'] = $yesterday;
                $bestsellersTableName = 'sales_bestsellers_aggregated_daily';
                break;
            case 'last_week':
                $daysArr = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                $firstDay = $this->_scopeConfig->getValue(
                    'general/locale/firstday',
                    ScopeInterface::SCOPE_STORE,
                    $storeId
                );

                $previousWeek = strtotime('-1 week +1 day', $currentDate->getTimestamp());
                $startWeek = strtotime('last ' . $daysArr[$firstDay] . ' midnight +1 day', $previousWeek);
                $endWeek = strtotime('+6 day', $startWeek);

                $periodFilter['from'] = $this->_localeDate->date($startWeek)->format('Y-m-d');
                $periodFilter['to'] = $this->_localeDate->date($endWeek)->format('Y-m-d');
                $bestsellersTableName = 'sales_bestsellers_aggregated_daily';
                break;
            case 'last_month':
                $firstDay = strtotime('first day of previous month', $currentDate->getTimestamp());
                $lastDay = strtotime('last day of previous month', $currentDate->getTimestamp());

                $periodFilter['from'] = $this->_localeDate->date($firstDay)->format('Y-m-d');
                $periodFilter['to'] = $this->_localeDate->date($lastDay)->format('Y-m-d');
                $bestsellersTableName = 'sales_bestsellers_aggregated_monthly';
                break;
            case 'last_year':
                $firstDay = strtotime('first day of previous year', $currentDate->getTimestamp());
                $lastDay = strtotime('last day of previous year', $currentDate->getTimestamp());

                $periodFilter['from'] = $this->_localeDate->date($firstDay)->format('Y-m-d');
                $periodFilter['to'] = $this->_localeDate->date($lastDay)->format('Y-m-d');
                break;
            default:
                $periodFilter = [];
        }
        /** End prepare filter by period */

        $_collection = $this->_addProductAttributesAndPrices($_collection);

        $_collection->getSelect()
            ->join(
                ['bestsellers' => $_collection->getTable($bestsellersTableName)],
                'e.entity_id = bestsellers.product_id AND bestsellers.store_id = ' . $this->getStoreId(),
                ['qty_ordered', 'rating_pos', 'period']
            )
            ->group('bestsellers.product_id')
            ->order('rating_pos');

        /** Filter products collection by period */
        if ($periodFilter) {
            $from = $periodFilter['from'];
            $to = $periodFilter['to'];

            $_collection->getSelect()
                ->where("bestsellers.period >= '$from'")
                ->where("bestsellers.period <= '$to'");
        }
        /** End filter products collection by period */

        /** Configurable products from simple product added as well into best seller list */
        $_conn = $_collection->getResource()->getConnection();
        $select = $_conn->select('*')
            ->from($_collection->getTable($bestsellersTableName . ' AS sbay'))
            ->joinLeft(
                ['cpsl' => $_collection->getTable('catalog_product_super_link')],
                'sbay.product_id = cpsl.product_id',
                ['parent_id']
            )
            ->where('sbay.store_id = ' . $this->getStoreId())
            ->where('cpsl.parent_id IS NOT NULL');

        /** Filter products collection by period */
        if ($periodFilter) {
            $from = $periodFilter['from'];
            $to = $periodFilter['to'];

            $select->where("sbay.period >= '$from'")
                ->where("sbay.period <= '$to'");
        }
        /** End filter products collection by period */

        $result = $_conn->fetchAll($select);
        $configurableParents = [];

        foreach ($result as $item) {
            $configurableParents[$item['parent_id']] = $item['rating_pos'];
        }

        $configurableProductsCollection = $this->_productCollectionFactory->create();
        $configurableProductsCollection->addAttributeToFilter('entity_id', ['in' => array_keys($configurableParents)]);
        $configurableProductsCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
        $configurableProductsCollection = $this->_addProductAttributesAndPrices($configurableProductsCollection);

        $items = clone $_collection;
        foreach ($_collection as $key => $item) {
            $_collection->removeItemByKey($key);
            $configurableParents[$key] = $item->getData('rating_pos');
        }

        asort($configurableParents);
        foreach ($configurableParents as $key => $ratingPos) {
            $item = $items->getItemById($key);
            if (!$item) {
                $item = $configurableProductsCollection->getItemById($key);
                if (!$item) {
                    continue;
                }
                $item->setData('rating_pos', $ratingPos);
            }

            if ($item) {
                $_collection->addItem($item);
            }
        }
        /** Configurable products from simple product added as well into best seller list */

        $_collection->addStoreFilter($this->getStoreId())->setCurPage(1);

        if ($limit && $limit > 0) {
            $_collection->setPageSize($limit);
        }

        return $_collection;
    }

    /**
     * Get sell slider products.
     *
     * @param Collection $_collection
     * @return Collection
     */
    protected function _getSellProductCollection($_collection)
    {
        $limit = $this->_getProductLimit('sell_products');
        $sortOrder = $this->_getSortOrder('sell_products');
        $random = ($sortOrder == SortOrder::SORT_RANDOM);

        if (!$limit || $limit == 0) {
            return [];
        }

        $_collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());

        if ($random) {
            $allIds = $_collection->getAllIds();
            $candidateIds = $_collection->getAllIds();
            $randomIds = [];
            $maxKey = count($candidateIds) - 1;
            while (count($randomIds) <= count($allIds) - 1) {
                $randomKey = random_int(0, $maxKey);
                $randomIds[$randomKey] = $candidateIds[$randomKey];
            }

            $_collection->addIdFilter($randomIds);
        } elseif ($sortOrder) {
            $this->_sortCollectionWithCustomOrder($_collection, $sortOrder);
        }

        $saleAttributeCode = 'sale';
        $addSaleAttributeToCollection = true;
        $saleCondition = '(at_sale.value = 1) OR ';
        try {
            $this->attributeRepository
                ->get(ProductAttributeInterface::ENTITY_TYPE_CODE, $saleAttributeCode);
        } catch (NoSuchEntityException $ex) {
            $addSaleAttributeToCollection = false;
            $saleCondition = '';
        }

        $_collection = $this->_addProductAttributesAndPrices($_collection)
            ->addAttributeToSelect('special_from_date', true)
            ->addAttributeToSelect('special_to_date', true);

        if ($addSaleAttributeToCollection) {
            $_collection->addAttributeToSelect($saleAttributeCode, true);
        }

        $_collection->addAttributeToSort(
            'news_from_date',
            'desc'
        )
            ->addStoreFilter($this->getStoreId())
            ->setCurPage(1);

        $startOfDay = $this->getStartOfDayDate();
        $endOfDay = $this->getEndOfDayDate();
        $_collection->getSelect()->where(
            "(" . $saleCondition .
            " (IF(at_special_from_date.value_id > 0, at_special_from_date.value, at_special_from_date_default.value)
             <= '$endOfDay')" .
            " AND (((((IF(at_special_to_date.value_id > 0, at_special_to_date.value, at_special_to_date_default.value)
             >= '$startOfDay')" .
            " OR (IF(at_special_to_date.value_id > 0, at_special_to_date.value, at_special_to_date_default.value)
             IS null))))))"
        );

        if ($limit && $limit > 0) {
            $_collection->setPageSize($limit);
        }

        return $_collection;
    }

    /**
     * @param Collection $_collection
     * @return Collection
     */
    protected function _getProductCollectionConditionBased($_collection)
    {
        $limit = $this->_getProductLimit('conditions_based_products');
        $sortOrder = $this->_getSortOrder('conditions_based_products');
        $random = ($sortOrder == SortOrder::SORT_RANDOM);

        if (!$limit || $limit == 0) {
            return [];
        }

        $_collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
        $_collection = $this->_addProductAttributesAndPrices($_collection)
            ->addStoreFilter($this->getStoreId())
            ->setCurPage(1);

        $conditions = $this->getConditions();
        $conditions->collectValidatedAttributes($_collection);
        $this->sqlBuilder->attachConditionToCollection($_collection, $conditions);
        $_collection->distinct(true);

        if ($random) {
            $allIds = $_collection->getAllIds();
            $candidateIds = $_collection->getAllIds();
            $randomIds = [];
            $maxKey = count($candidateIds) - 1;
            while (count($randomIds) <= count($allIds) - 1) {
                $randomKey = random_int(0, $maxKey);
                $randomIds[$randomKey] = $candidateIds[$randomKey];
            }

            $_collection->addIdFilter($randomIds);
        } elseif ($sortOrder) {
            $this->_sortCollectionWithCustomOrder($_collection, $sortOrder);
        }

        if ($limit && $limit > 0) {
            $_collection->setPageSize($limit);
        }

        return $_collection;
    }

    /**
     * Get recently viewed slider products.
     *
     * @param Collection $_collection
     * @return Collection
     */
    protected function _getRecentlyViewedCollection($_productBlockCollection)
    {
        $limit = $this->_getProductLimit('recently_viewed');
        $sortOrder = $this->_getSortOrder('recently_viewed');
        $random = ($sortOrder == SortOrder::SORT_RANDOM);
        $result;

        if ($limit == 0) {
            return [];
        }

        $_productBlockCollection = $this->_viewProductsBlock->getItemsCollection();

        if ($random) {
            $allIds = $_productBlockCollection->getAllIds();
            $candidateIds = $_productBlockCollection->getAllIds();
            $randomIds = [];
            $maxKey = count($candidateIds) - 1;
            while (count($randomIds) <= count($allIds) - 1) {
                $randomKey = random_int(0, $maxKey);
                $randomIds[$randomKey] = $candidateIds[$randomKey];
            }

            $_productBlockCollection->addIdFilter($randomIds);
        } elseif ($sortOrder) {
            $this->_sortCollectionWithCustomOrder($_productBlockCollection, $sortOrder);
        }

        if ($limit && $limit > 0) {
            $_productBlockCollection->setPageSize($limit);
        }
        else
         {
         $result=$_productBlockCollection;
         }

        return $result;
    }

    /**
     * @param $categoryId
     * @return array|AbstractDb
     */
    protected function _getCustomCategoryCollection($categoryId)
    {
        $limit = $this->_getProductLimit('category_products');
        $sortOrder = $this->_getSortOrder('category_products');
        $random = ($sortOrder == SortOrder::SORT_RANDOM);

        if ($limit == 0) {
            return [];
        }

        $category = $this->_categoryFactory->create()->load($categoryId);

        $_collection = $category->getProductCollection();
        $_collection->addAttributeToSelect('*');

        if ($random) {
            $_collection->getSelect()->order('RAND()');
        } elseif ($sortOrder) {
            $this->_sortCollectionWithCustomOrder($_collection, $sortOrder);
        }

        if ($limit && $limit > 0) {
            $_collection->setPageSize($limit);
        }

        return $_collection;
    }

    /**
     * Get related slider products.
     *
     * @return Collection
     */
    public function getProductCollectionRelated()
    {
        if (!$this->_currentProduct) {
            return [];
        }

        return $this->getRelatedProducts($this->_currentProduct);
    }

    /**
     * Retrieve array of related products.
     *
     * @return array
     */
    public function getRelatedProducts($currentProduct)
    {
        if (!$currentProduct->hasRelatedProducts()) {
            $products = [];
            $_collection = $currentProduct->getRelatedProductCollection();
            $_collection->addAttributeToSelect('*');
            foreach ($_collection as $product) {
                $products[] = $product;
            }
            $currentProduct->setRelatedProducts($products);
        }

        return $currentProduct->getData('related_products');
    }

    /**
     * Get up-sell slider products.
     *
     * @return Collection
     */
    public function getProductCollectionUpSell()
    {
        if (!$this->_currentProduct) {
            return [];
        }
        return $this->getUpSellProducts($this->_currentProduct);
    }

    /**
     * Retrieve array of up sell products.
     *
     * @return array
     */
    public function getUpSellProducts($currentProduct)
    {
        if (!$currentProduct->hasUpSellProducts()) {
            $products = [];
            $_collection = $currentProduct->getUpSellProductCollection();
            $_collection->addAttributeToSelect('*');
            foreach ($_collection as $product) {
                $products[] = $product;
            }
            $currentProduct->setUpSellProducts($products);
        }

        return $currentProduct->getData('up_sell_products');
    }

    /**
     * Get cross-sell slider products.
     *
     * @return Collection
     */
    public function getProductCollectionCrossSell()
    {
        if (!$this->_currentProduct) {
            return [];
        }

        return $this->getCrossSellProducts($this->_currentProduct);
    }

    /**
     * Retrieve array of cross sell products
     *
     * @return array
     */
    public function getCrossSellProducts($currentProduct)
    {
        if (!$currentProduct->hasCrossSellProducts()) {
            $products = [];
            $_collection = $currentProduct->getCrossSellProductCollection();
            $_collection->addAttributeToSelect('*');
            foreach ($_collection as $product) {
                $products[] = $product;
            }
            $currentProduct->setCrossSellProducts($products);
        }

        return $currentProduct->getData('cross_sell_products');
    }

    /**
     * Retrieve the products limit based on type.
     *
     * @param $type
     * @return int
     */
    protected function _getProductLimit($type)
    {
        return $this->_helperProducts->getProductLimit($type);
    }

    /**
     * Retrieve the products random sort flag based on type.
     *
     * @param $type
     * @return mixed
     * @deprecated
     */
    protected function _getRandomSort($type)
    {
        return $this->_helperProducts->getRandomSort($type);
    }

    /**
     * @param string $type
     * @return int
     */
    public function _getSortOrder($type)
    {
        return $this->_helperProducts->getSortOrder($type);
    }

    /**
     * Get start of day date.
     * @return string
     */
    public function getStartOfDayDate()
    {
        return $this->_localeDate->date()->setTime(0, 0, 0)->format(self::TIME_FORMAT);
    }

    /**
     * Get end of day date.
     * @return string
     */
    public function getEndOfDayDate()
    {
        return $this->_localeDate->date()->setTime(23, 59, 59)->format(self::TIME_FORMAT);
    }

    /**
     * Retrieve the current store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * @return mixed
     */
    public function isHoverImageEnabled()
    {
        return $this->_helperCustom->isHoverImageEnabled();
    }

    /**
     * @param $category
     * @return bool
     */
    protected function _getCategoryIdFrom($category)
    {
        $value = explode('/', $category);
        $categoryId = false;

        if (isset($value[0]) && isset($value[1]) && $value[0] == 'category') {
            $categoryId = $value[1];
        }

        return $categoryId;
    }

    /**
     * @param $collection
     * @param $sortOrder
     */
    protected function _sortCollectionWithCustomOrder($collection, $sortOrder)
    {
        $sortByAttribute = '';
        $sortByOrder = '';
        switch ($sortOrder) {
            case SortOrder::SORT_ID_ASC:
                $sortByAttribute = 'entity_id';
                $sortByOrder = 'ASC';
                break;
            case SortOrder::SORT_ID_DESC:
                $sortByAttribute = 'entity_id';
                $sortByOrder = 'DESC';
                break;
            case SortOrder::SORT_PRICE_ASC:
                $sortByAttribute = 'price';
                $sortByOrder = 'ASC';
                break;
            case SortOrder::SORT_PRICE_DESC:
                $sortByAttribute = 'price';
                $sortByOrder = 'DESC';
                break;
            case SortOrder::SORT_NAME_ASC:
                $sortByAttribute = 'name';
                $sortByOrder = 'ASC';
                break;
            case SortOrder::SORT_NAME_DESC:
                $sortByAttribute = 'name';
                $sortByOrder = 'DESC';
                break;
            default:
                return;
        }
        if ($sortByAttribute && $sortByOrder) {
            $collection->setOrder($sortByAttribute, $sortByOrder);
        }
    }

    /**
     * Get conditions
     *
     * @return Combine
     */
    protected function getConditions()
    {
        $conditions = $this->getData('conditions_encoded')
            ? $this->getData('conditions_encoded')
            : $this->getData('conditions');

        if ($conditions) {
            $conditions = $this->conditionsHelper->decode($conditions);
        }

        foreach ($conditions as $key => $condition) {
            if (!empty($condition['attribute'])) {
                if (in_array($condition['attribute'], ['special_from_date', 'special_to_date'])) {
                    $conditions[$key]['value'] = date(self::TIME_FORMAT, strtotime($condition['value']));
                }

                if ($condition['attribute'] == 'category_ids') {
                    $conditions[$key] = $this->updateAnchorCategoryConditions($condition);
                }
            }
        }

        $this->rule->loadPost(['conditions' => $conditions]);
        return $this->rule->getConditions();
    }

    /**
     * Update conditions if the category is an anchor category
     *
     * @param array $condition
     * @return array
     */
    private function updateAnchorCategoryConditions(array $condition): array
    {
        if (array_key_exists('value', $condition)) {
            $categoryId = $condition['value'];

            try {
                $category = $this->categoryRepository->get($categoryId, $this->_storeManager->getStore()->getId());
            } catch (NoSuchEntityException $e) {
                return $condition;
            }

            $children = $category->getIsAnchor() ? $category->getChildren(true) : [];
            if ($children) {
                $children = explode(',', $children);
                $condition['operator'] = "()";
                $condition['value'] = array_merge([$categoryId], $children);
            }
        }

        return $condition;
    }
}
