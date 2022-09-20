<?php

namespace I95Dev\CatalogSetup\Controller\Cart;

use Exception;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class for Add
 * I95Dev\CatalogSetup\Controller\Cart
 */
class Add extends \Bss\ConfigurableMatrixView\Controller\Cart\Add
{

    /**
     * Add product to shopping cart action
     */
    public function execute()
    {
        if (!$this->helperCart->validateFormKey($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $params = $this->getRequest()->getParams();
        $addedProducts = $product_fail = [];
        $productId = (int)$this->getRequest()->getPost('product');
        $productIds = $this->getRequest()->getPost('super_attribute_' . $productId);
        foreach ($productIds as $k => $super_attribute) {
            try {
                $qty = $this->getRequest()->getPost('qty_' . $productId . '_' . $k, 0);
                $product = $this->getProductMTV($productId);
                if ($qty <= 0 || !$product) {
                    continue;
                }
                $paramsr = [];
                $paramsr['product'] = $productId;
                $paramsr['qty'] = $qty;
                $paramsr['super_attribute'] = $super_attribute;
                $this->checkAttribute($params, $paramsr);
                $paramsr['options'] = isset($params['options']) ? $params['options'] : [];
                $paramsr['options'] = (!empty($this->options)) ? $this->options : $paramsr['options'];
                $params_new = isset($params['options']) ? $params['options'] : [];
                $childProductId = $this->getRequest()->getPost('child_product_' . $productId . '_' . $k);
                $childProduct = $this->getProductMTV($childProductId);

                if (isset($params['custmizeHemmingArray'][$childProductId]) &&
                    $params['custmizeHemmingArray'][$childProductId] != "") {
                    $childProductsCustmization = explode(",", $params['custmizeHemmingArray'][$childProductId]);
                    $hemming_option_id = $this->getRequest()->getPost('heming_options_' . $productId);
                    foreach ($childProductsCustmization as $childProductsCustmizations) {
                        $childProductsFinals = explode("-", $childProductsCustmizations);
                        $paramsr['qty'] = (int)$childProductsFinals[0];
                        $paramsr['options'] = [$hemming_option_id => $childProductsFinals[2]];
                        $product = $this->getProductMTV($productId);
                        $this->cart->addProduct($product, $paramsr);
                        $this->getCustomOption($product);
                        $this->returnAddedProduct($addedProducts, $childProduct);
                    }
                } else {
                    $paramsr['options'] = $params_new;
                    $product = $this->getProductMTV($productId);
                    $this->cart->addProduct($product, $paramsr);
                    $this->getCustomOption($product);
                    $this->returnAddedProduct($addedProducts, $childProduct);
                }
            } catch (LocalizedException $e) {
                $product_fail = $this->getMessageError($product_fail, $childProduct, $e);

                $cartItem = $this->cart->getQuote()->getItemByProduct($product);
                $this->removeCartItem($cartItem);
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t add this item to your shopping cart right now.')
                );
                $this->logger->critical($e);
            }
        }
        $this->saveAdd($addedProducts);
        $url = $this->checkoutSession->getRedirectUrl(true);

        $product_poup['errors'] = $product_fail;
        $product_poup['product_qtys'] = $this->getQtyofProductInCart();
        $fail = empty($product_fail);
        return $this->result($product_poup, $url, $fail);
    }
}
