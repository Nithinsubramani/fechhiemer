<?php

namespace I95Dev\CatalogSetup\Block\Product\View;

use Magento\Catalog\Model\Product;

class ConfigurableMatrix extends \Bss\ConfigurableMatrixView\Block\Product\View\ConfigurableMatrix
{

    public function getPriceHtml(
        Product $product,
        $priceType = null,
        array $arguments = []
    ) {
        if (!isset($arguments['zone'])) {
            $arguments['zone'] = 'item_view';
        }

        $priceRender = $this->getLayout()->getBlock('product.price.render.default');

        $orgprice = $product->getPrice();
        $specialprice = $product->getSpecialPrice();
        $specialfromdate = $product->getSpecialFromDate();
        $specialtodate = $product->getSpecialToDate();
        $today = time();

        $price = '';
        if ($priceRender && $priceType) {
            $price = $priceRender->render(
                $priceType,
                $product,
                $arguments
            );
        }
        if (!$specialprice) {
            $specialprice = $orgprice;
        }
        if ($specialprice < $orgprice) {
            // phpcs:disable
            if ((is_null($specialfromdate) && is_null($specialtodate)) ||
                ($today >= strtotime($specialfromdate) && is_null($specialtodate)) ||
                ($today <= strtotime($specialtodate) && is_null($specialfromdate)) ||
                ($today >= strtotime($specialfromdate) && $today <= strtotime($specialtodate))) {
                return $price;
            }
        } else {
            return str_replace("old-price", "old-price hidetier-price", $price);
        }
    }
}
