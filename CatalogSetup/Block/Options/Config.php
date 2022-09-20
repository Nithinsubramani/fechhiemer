<?php

namespace I95Dev\CatalogSetup\Block\Options;

use Exception;
use Itoris\DynamicProductOptions\Model\Options;
use Magento\Backend\Model\Session;
use Magento\Backend\Model\Session\Quote;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Option;
use Magento\Checkout\Model\Cart;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Json;

/**
 * Class for Config
 * I95Dev\CatalogSetup\Block\Options
 */
class Config extends \Magento\Catalog\Block\Product\View\Options
{

    /**
     * @var bool
     */
    protected static $isJsCssAdded = false;

    /**
     * @var null
     */
    protected $config = null;
    /**
     * @var bool
     */
    protected $isEnabled = false;
    /**
     * @var array
     */
    protected $associatedProductBlocks = [];
    /** @var ObjectManagerInterface|null */
    public $_objectManager = null;//phpcs:ignore

    /**
     * @var Manager
     */
    protected $moduleManager;
    /**
     * Config constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param Data $catalogData
     * @param EncoderInterface $jsonEncoder
     * @param Option $option
     * @param Registry $registry
     * @param ArrayUtils $arrayUtils
     * @param Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        Data $catalogData,
        EncoderInterface $jsonEncoder,
        Option $option,
        Registry $registry,
        ArrayUtils $arrayUtils,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct(
            $context,
            $pricingHelper,
            $catalogData,
            $jsonEncoder,
            $option,
            $registry,
            $arrayUtils,
            $data
        );
    }

    /**
     * @throws NoSuchEntityException
     * phpcs:disable
     */
    protected function _construct()
    {

        $this->isEnabled = $this->getDataHelper()->getSettings()->getEnabled() &&
            $this->getDataHelper()->isRegisteredAutonomous() && $this->getProduct();
        if ($this->isEnabled) {
            if ($this->getProduct()->getTypeId() != 'grouped') {
                $getMatrixOrNot = $this->getMatrixOrNot();
                $this->moduleManager = $this->_objectManager->create(Manager::class);
                if ($this->moduleManager->isOutputEnabled('Bss_ConfigurableMatrixView')) {
                    if ($getMatrixOrNot != 1) {
                        $this->setTemplate('Itoris_DynamicProductOptions::config.phtml');
                    }
                } else {
                    $this->setTemplate('Itoris_DynamicProductOptions::config.phtml');
                }

                $this->isEnabled = $this->getConfig()->getId();
            }
        }
        parent::_construct();
    }
    /** phpcs:enable */

    /**
     * @return Options
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        // phpcs:disable
        if (is_null($this->config)) {
            /** @var Options config */
            $this->config = $this->_objectManager->create(Options::class)
                ->setStoreId(
                    $this->_storeManager->getStore()->getId()
                )
                ->load($this->getProductId());
            if (!$this->config->getId()) {
                $this->config->setStoreId(0)->load($this->getProductId());
            }
            if (!$this->config->getId()) {
                $this->config->setProductId($this->getProductId());
            }
        }
        return $this->config;
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return $this->getProduct()->getId();
    }

    /**
     * Get getMatrix Or Not
     *
     * @return int
     */
    public function getMatrixOrNot()
    {
        return $this->getProduct()->getConfigurableMatrixView();
    }

    /**
     * @return Product
     */

    public function getProduct()
    {
        if ($this->getData('product')) {
            $this->setTemplate('grouped/config.phtml');
            return $this->getData('product');
        } else {
            if ($this->_request->getParam('handles')) {
                return false; //fix for conflict with Varnish
            }
            try {
                $product = parent::getProduct();
            } catch (Exception $e) {
                $product = false;
            }
            return $product;
        }
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getStyles()
    {
        return $this->getConfig()->getCssAdjustments();
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getExtraJs()
    {
        return $this->getConfig()->getExtraJs();
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getAbsolutePricing()
    {
        return $this->getConfig()->getAbsolutePricing();
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getAbsoluteSku()
    {
        return $this->getConfig()->getAbsoluteSku();
    }

    /**
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function getAbsoluteWeight()
    {
        return $this->getConfig()->getAbsoluteWeight();
    }

    /**
     * @param array $config
     * @return string
     * @throws NoSuchEntityException
     */
    public function getJsObjectConfig(array $config = [])
    {
        $errorMessage = $this->getDataHelper()->getOptionErrorsMessage();
        if ($errorMessage) {
            $errorMessage = $this->getDataHelper()->prepareErrorMessage($errorMessage);
        }
        $defaultConfig = [
            'form_style' => $this->getConfig()->getFormStyle(),
            'appearance' => $this->getConfig()->getAppearance(),
            'absolute_pricing' => $this->getConfig()->getAbsolutePricing(),
            'absolute_sku' => $this->getConfig()->getAbsoluteSku(),
            'absolute_weight' => $this->getConfig()->getAbsoluteWeight(),
            'product_id' => $this->getProductId(),
            'is_configured' => $this->isEditAction(),
            'is_grouped' => $this->hasData('product'),
            'option_errors' => $this->_getOptionErrors(),
            'error_message' => $errorMessage,
            'product_type' => $this->getProduct()->getTypeId(),
            'configure_product_message' => $this->escapeHtml(__('Please configure the product')),
            //magento 1.9 ??? false
            //'mage19'          => $this->getDataHelper()->isMagento19(),
            'out_of_stock_message' => $this->escapeHtml(__('out of stock')),
            'section_conditions' => [],
            'options_qty' => $this->getQuoteProductOptionsQtys(),
            'extra_js' => $this->getExtraJs()
        ];

        $config = array_merge($defaultConfig, $config);

        return Zend_Json::encode($config);
    }

    /**
     * @return array
     */
    public function getQuoteProductOptionsQtys()
    {
        $request = $this->_request;
        // phpcs:disable
        if ($request->getControllerName() == 'cart' &&
            $request->getActionName() == 'configure' && intval($request->get('id')) > 0) {
            /** @var Cart $cartModel */
            $cartModel = $this->_objectManager->create(Cart::class);
            $item = $cartModel->getQuote()->getItemById(intval($request->get('id')));
            if ($item && $item->getId()) {
                return (array)$item->getBuyRequest()->getData('options_qty');
            }
        }
        if ($this->_request->getControllerName() == 'order_create') { //moo
            /** @var Quote $quoteSingleton */
            $quoteSingleton = $this->_objectManager->get(Quote::class);
            // phpcs:disable
            $item = $quoteSingleton->getQuote()->getItemById(intval($request->get('id')));
            if ($item && $item->getId()) {
                return (array)$item->getBuyRequest()->getData('options_qty');
            }
        }
        return [];
    }

    /**
     * @return array
     * phpcs:disable
     */
    protected function _getOptionErrors()
    {
        $result = [];
        $errors = $this->_objectManager->get(Session::class)->getDynamicOptionsErrors(true);
        if (is_array($errors)) {
            foreach ($errors as $optionId => $message) {
                $result[] = [
                    'option_id' => $optionId,
                    'message' => $message,
                ];
            }
        }
        return $result;
    }
    /** phpcs:enable */

    /**
     * @return bool
     */
    public function isEditAction()
    {
        return $this->getRequest()->getActionName() == 'configure';
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     * phpcs:disable
     */
    public function getSections()
    {
        $sections = $this->getConfig()->getSections();
        $sectionsObjects = [];
        foreach ($sections as $section) {
            if ($section) {
                if (!isset($section['fields'])) {
                    $section['fields'] = [];
                }
                $fieldsObjects = [];
                foreach ($section['fields'] as $field) {
                    if ($field) {
                        if (!isset($field['items'])) {
                            $field['items'] = [];
                        }
                        $itemsObjects = [];
                        foreach ($field['items'] as $item) {
                            if ($item) {
                                $itemsObjects[] = $item;
                            }
                        }
                        $field['items'] = $itemsObjects;
                        $fieldsObjects[] = new DataObject($field);
                    }
                }
                $section['fields'] = $fieldsObjects;
                $sectionsObjects[] = new DataObject($section);
            }
        }
        return $sectionsObjects;
    }

    /**
     * @param $field
     * @return html|null
     * @throws LocalizedException
     */
    public function getFieldHtml($field)
    {
        $block = null;

        switch ($field->getType()) {
            case 'image':
                $block = $this->getLayout()->createBlock(Itoris\DynamicProductOptions\Block\Options\Type\Image::class);
                break;
            case 'html':
                $block = $this->getLayout()->createBlock(Itoris\DynamicProductOptions\Block\Options\Type\Html::class);
                break;
            default:
        }
        if ($block) {
            return $block->setField($field)->toHtml();
        } else {
            return null;
        }
    }

    /**
     * @param $option
     * @return bool
     */
    public function isSystemOption($option)
    {
        return !($option->getType() == 'image' || $option->getType() == 'html');
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     * phpcs:disable
     */
    public function getAllFieldsJson()
    {
        $sections = $this->getConfig()->getSections();
        $fields = [];
        $priceCurrency = $this->_objectManager->get(PriceCurrencyInterface::class);
        foreach ($sections as $section) {
            if ($section && is_array($section['fields'])) {
                foreach ($section['fields'] as $field) {
                    if ($field) {
                        if (isset($field['comment']) && $field['comment']) {
                            $field['comment'] = (string)__($field['comment']);
                        }
                        if (isset($field['default_value']) && $field['default_value']) {
                            $field['default_value'] = (string)__($field['default_value']);
                        }
                        if (isset($field['tooltip']) && $field['tooltip']) {
                            $field['tooltip'] = $this->parseMediaVariables($field['tooltip']);
                        }
                        if (isset($field['items']) && is_array($field['items'])) {
                            foreach ($field['items'] as $key => $item) {
                                if (isset($item['sku_is_product_id_linked']) &&
                                    (int)$item['sku_is_product_id_linked'] && (int)$item['sku_is_product_id']) {
                                    $product = $this->_objectManager->create(Product::class)
                                        ->load((int)$item['sku']);
                                    $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')
                                        ->getTierPriceList();
                                    $tierPrices = [];
                                    foreach ($tierPricesList as $tierPrice) {
                                        $tierPrices[] = ['qty' => $tierPrice['price_qty'],
                                            'price' => (float)$tierPrice['website_price'], 'price_type' => 'fixed'];
                                    }
                                    if (count($tierPrices)) {
                                        $field['items'][$key]['tier_price'] = json_encode($tierPrices);
                                    } else {
                                        unset($field['items'][$key]['tier_price']);
                                    }
                                    if (floatval($product->getSpecialPrice()) > 0 &&
                                        floatval($product->getSpecialPrice()) < floatval($product->getPrice())) {
                                        $field['items'][$key]['compare_at'] = $product->getTierPrice(1);
                                    }
                                } elseif (isset($field['items'][$key]['tier_price']) &&
                                    $field['items'][$key]['tier_price']) {
                                    $tierPrices = json_decode($field['items'][$key]['tier_price'], true);
                                    if (is_array($tierPrices) && count($tierPrices)) {
                                        foreach ($tierPrices as &$tierPrice) {
                                            $tierPrice['price'] = $priceCurrency->convert($tierPrice['price']);
                                        }
                                        $field['items'][$key]['tier_price'] = json_encode($tierPrices);
                                    } else {
                                        unset($field['items'][$key]['tier_price']);
                                    }
                                }
                                if (isset($item['tooltip']) && $item['tooltip']) {
                                    $field['items'][$key]['tooltip'] = $this->parseMediaVariables($item['tooltip']);
                                }
                                if (isset($item['swatchhtml']) && $item['swatchhtml']) {
                                    $field['items'][$key]['swatchhtml'] =
                                        $this->parseMediaVariables($item['swatchhtml']);
                                }
                            }
                        }
                        $fields[] = $field;
                    }
                }
            }
        }
        return Zend_Json::encode($fields);
    }

    /**
     * @param $option
     * @return string
     */
    public function getOptionPrice($option)
    {
        if ($option->getPrice()) {
            if ($option->getPriceType() == 'percent') {
                $basePrice = $this->getProduct()->getFinalPrice(1);
                $price = $basePrice * ($option->getPrice() / 100);
            } else {
                $price = $option->getPrice();
            }
            return $this->_formatPrice($price);
        }
        return '';
    }

    /**
     * @param $price
     * @param bool $flag
     * @return string
     * phpcs:disable
     */
    protected function _formatPrice($price, $flag = true)
    {
        if ($price == 0) {
            return '';
        }
        $taxHelper = $this->getTaxHelper();
        $store = $this->getProduct()->getStore();

        $sign = '+';
        if ($price < 0) {
            $sign = '-';
            $price = 0 - $price;
        }

        $priceStr = $sign;
        $_priceInclTax = $this->getPrice($price, true);
        $_priceExclTax = $this->getPrice($price);
        /** @var \Magento\Framework\Pricing\Helper\Data $pricingHelper */
        $pricingHelper = $this->_objectManager->create(\Magento\Framework\Pricing\Helper\Data::class);
        if ($taxHelper->displayPriceIncludingTax()) {
            $priceStr .= $pricingHelper->currencyByStore($_priceInclTax, $store, true, $flag);
        } elseif ($taxHelper->displayPriceExcludingTax()) {
            $priceStr .= $pricingHelper->currencyByStore($_priceExclTax, $store, true, $flag);
        } elseif ($taxHelper->displayBothPrices()) {
            $priceStr .= $pricingHelper->currencyByStore($_priceExclTax, $store, true, $flag);
            if ($_priceInclTax != $_priceExclTax) {
                $priceStr .= ' (' . $sign . $pricingHelper
                        ->currencyByStore($_priceInclTax, $store, true, $flag) . ' ' .
                    $this->escapeHtml(__('Incl. Tax')) . ')';
            }
        }

        if ($flag) {
            $priceStr = '<span class="price-notice">' . $priceStr . '</span>';
        }

        return $priceStr;
    }
    /** phpcs:enable */

    /**
     * @param $price
     * @param null $includingTax
     * @return float
     */
    public function getPrice($price, $includingTax = null)
    {
        // phpcs:disable
        if (!is_null($includingTax)) {
            $price = $this->getCatalogHelper()->getTaxPrice($this->getProduct(), $price, true);
        } else {
            $price = $this->getCatalogHelper()->getTaxPrice($this->getProduct(), $price);
        }
        return $price;
    }

    /**
     * @return string|null
     * phpcs:disable
     */
    protected function _toHtml()
    {
        if ($this->isEnabled) {
            if ($this->getProduct()->getTypeId() == 'grouped') {
                $this->setTemplate('grouped.phtml');
                $html = parent::_toHtml();
                foreach ($this->getProduct()->getTypeInstance(true)
                             ->getAssociatedProducts($this->getProduct()) as $product) {
                    $product->load($product->getId());
                    $subBlock = $this->_objectManager->create(Itoris\DynamicProductOptions\Block\Options\Config::class)
                        ->setProduct($product)->setTemplate('Itoris_DynamicProductOptions::grouped/config.phtml');
                    $html .= $subBlock->toHtml();
                }
                return $html;
            } else {
                return parent::_toHtml();
            }
        }
        return null;
    }
    /** phpcs:enable */

    /**
     * @return Config
     * @throws LocalizedException
     * phpcs:disable
     */
    protected function _prepareLayout()
    {
        if ($this->isEnabled) {
            $head = $this->getLayout()->getBlock('head');
            if (!self::$isJsCssAdded) {
                if ($head) {
                    $head->addCss('main.css');
                    self::$isJsCssAdded = true;
                }
            }
        }
        return parent::_prepareLayout();
    }
    /** phpcs:enable */

    /**
     * @param $section
     * @param $allConditions
     * @return mixed
     */
    public function prepareSectionConditions($section, $allConditions)
    {
        if ($section->getVisibilityCondition()) {
            $allConditions[] = [
                'order' => $section->getOrder(),
                'visibility' => $section->getVisibility(),
                'visibility_action' => $section->getVisibilityAction(),
                'visibility_condition' => $section->getVisibilityCondition(),
            ];
        } else {
            $allConditions[] = [
                'order' => $section->getOrder(),
                'visibility' => $section->getVisibility(),
                'visibility_action' => $section->getVisibilityAction(),
                'visibility_condition' => '{"type":"all","value":1,"conditions":[]}',
            ];
        }

        return $allConditions;
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getDataHelper()
    {
        return $this->_objectManager->create(\Itoris\DynamicProductOptions\Helper\Data::class);
    }

    /**
     * @return \Magento\Tax\Helper\Data
     */
    public function getTaxHelper()
    {
        return $this->_objectManager->create(\Magento\Tax\Helper\Data::class);
    }

    /**
     * @return Data
     */
    public function getCatalogHelper()
    {
        return $this->_objectManager->create(Data::class);
    }

    /**
     * @return array
     */
    public function getTierPrices()
    {
        $tierPrices = [];
        $product = $this->getProduct();
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        foreach ($tierPricesList as $tierPrice) {
            $tierPrices[] = ['qty' => $tierPrice['price_qty'],
                'price' => (float)$tierPrice['website_price']];
        }
        return $tierPrices;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return [
            'Qty' => __('Qty'),
            'Buy %1 for %2 each' => __('Buy %1 for %2 each'),
            'Search...' => __('Search...'),
            'Sorry, nothing found...' => __('Sorry, nothing found...'),
            'Was: %1' => __('Was: %1')
        ];
    }

    /**
     * @param $str
     * @return string|string[]
     */
    public function parseMediaVariables($str)
    {
        $str = __($str);
        $str = str_replace(
            [
                '{{media url=&quot;',
                '{{store direct_url=&quot;', '&quot;}}'
            ],
            [
                '{{media url="', '{{store direct_url="', '"}}'
            ],
            $str
        );

        preg_match_all('/{{media url=\"(.*?)\"}}/', $str, $matches);
        $mediaUrl = $this->getMediaUrl();
        foreach ($matches[0] as $key => $match) {
            $str = str_replace($matches[0][$key], $mediaUrl . $matches[1][$key], $str);
        }

        preg_match_all('/{{store direct_url=\"(.*?)\"}}/', $str, $matches);
        $baseUrl = $this->getBaseUrl();
        foreach ($matches[0] as $key => $match) {
            $str = str_replace($matches[0][$key], $baseUrl . $matches[1][$key], $str);
        }
        return $str;
    }

    /**
     * @return mixed
     */
    public function getMediaUrl()
    {
        $objectManager = ObjectManager::getInstance();
        $store = $objectManager->get(StoreManagerInterface::class)->getStore(0);
        return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        $objectManager = ObjectManager::getInstance();
        $store = $objectManager->get(StoreManagerInterface::class)->getStore();
        return $store->getBaseUrl();
    }
}
