/**
 * BSS Commerce Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://bsscommerce.com/Bss-Commerce-License.txt
 *
 * @category   BSS
 * @package    Bss_ConfigurableMatrixView
 * @author     Extension Team
 * @copyright  Copyright (c) 2017-2018 BSS Commerce Co. ( http://bsscommerce.com )
 * @license    http://bsscommerce.com/Bss-Commerce-License.txt
 */
define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'mage/template',
    'Bss_ConfigurableMatrixView/js/table',
    'Magento_Swatches/js/swatch-renderer',
    'mage/translate'
], function ($, priceUtils, mageTemplate) {
    'use strict';
    $.widget('mage.ConfigurableMatrixView', {
        options: {
            info_html_child:{},
            childproduct:{},
            product_qtys:[],
            matrixview_url:'',
            priceRange:{},
            priceFormat:'',
            show_button_qty:'',
            cks_tier_price:'',
            cks_tier_price_html:'',
            tier_price_calculate:'',
            price_display_type:'',
            taxRate:'',
            zIndex:'',
            priceConfig: null,
        },

        _create: function () {
            localStorage.removeItem("option_amountMVT");
            var $widget = this;
            $widget.getQtyofProductInCart();
            $widget._RenderTable();
            $widget._RenderProduct();
            $widget._EventListener();
        },

        _RenderTable: function () {
            var $widget = this,
                info_html_child = $widget.options.info_html_child;
            if(!$.isEmptyObject($widget.options.priceRange)){
                if ($('.product-info-price>.price-box').length) {
                    var priceRangeHtml = '<div class="bss-price-range"><div class="price-from"><span>'
                        + $.mage.__('From:') + '</span>' + $widget.options.priceRange.min + '</div><div class="price-to"><span>'
                        + $.mage.__('To:') + '</span>' + $widget.options.priceRange.max + '</div></div>';
                    $('.product-info-price>.price-box').html(priceRangeHtml).css('width','auto');
                }

            }

            if ($('[data-role="tier-price-block"]').length) {
                $('[data-role="tier-price-block"]').attr('data-mattrixv', 'tier-price-block').removeAttr('data-role');
            }

            if ($widget.options.cks_tier_price_html === '1') {
                var product_id = $('input[name="product"]').val();
                if (product_id != '') {
                    if ($('.product-info-main .prices-tier.items').length > $('.product-options-wrapper .prices-tier.items').length){
                        $('.product-info-main .prices-tier.items').remove();
                        $(info_html_child['tier_price'][product_id]).insertAfter(".product-info-main > .product-info-price");
                    } else {
                        if ($('[data-mattrixv="tier-price-block"]').length) {
                            $('[data-mattrixv="tier-price-block"]').html(info_html_child['tier_price'][product_id]).show();
                        }else{
                            var html_tier_price = '<div id="tier-price-'+ product_id +'" class="tier-price">'+ info_html_child['tier_price'][product_id] +'</div>';
                            $('.product-info-main .product-info-price').append(html_tier_price);
                        }
                    }
                }
            } else {
                $('.product-info-main .prices-tier.items').remove();
            }

            // insert button +-
            if($widget.options.show_button_qty == 1){
                $(".child-product-matrix > .qty").spinner({
                    min: 0,
                    step: 1,
                }).parent().find(".ui-spinner-button").empty().append("<span class='custom-button-bss'></span>");
            }

            if ($("#bss-matrixview").width() >= $(window).width()) {
                $(".block-bss-matrixview").width($(window).width() - 30);
            }
            $(window).resize(function(){
                if ($("#bss-matrixview").width() >= $(window).width()) {
                    $(".block-bss-matrixview").width($(window).width() - 30);
                }
            })

            setTimeout(function(){
                $("#bss-matrixview").tableCFMatrixView().css('visibility','visible');
                $widget._painDiagonalLine();
            },100)

            var href = $('#product_addtocart_form').attr('action');
            $('#product_addtocart_form').attr('action',href.replace('checkout/cart','matrixview/cart'));
        },

        _RenderProduct: function () {
            var $widget = this,
                childproduct = $widget.options.childproduct,
                cks_tier_price_html = $widget.options.cks_tier_price_html,
                info_html_child = $widget.options.info_html_child,
                

            $('#bss-matrixview .child-product-matrix').each(function(){
                 products = [];
                $(this).find('.super_attribute_matrix').each(function(){
                    var option = $(this);
                    var attribute = childproduct["attributes"][$(this).data('attribute-id')]['options'];
                    $.each(attribute, function (index, value){
                        if (value['id'] == $(option).val()) {
                            products.push(value['products']);
                        }
                    });
                })
                var product = products.shift().filter(function(v) {
                    return products.every(function(a) {
                        return a.indexOf(v) !== -1;
                    });
                });

                if(product[0] && product.length == 1){
                    var name_qty = $(this).find('.qty').attr('name');
                    $(this).find('.qty').attr('id', "qty_hemming_"+product[0]);
                    $(this).find('.ui-spinner-up').attr('data-up', product[0]);
                    $(this).find('.ui-spinner-down').attr('data-down', product[0]);
                    $(this).find('.popup-modal-customize').attr('id', "popup-modal-customize"+product[0]);
                    $(this).append('<input type="hidden" class="child-product" name="child_product'+ name_qty.replace('qty','') +'" value="'+ product[0] +'">')
                    if (info_html_child['final_price'][product[0]] != 'undefined') {
                        $(this).append(info_html_child['final_price'][product[0]]);
                    }


                    $(this).find('*').css('visibility','')
                    if (cks_tier_price_html == ''){
                        if (typeof info_html_child['tier_price'][product[0]] != 'undefined') {
                            if (info_html_child['tier_price'][product[0]].trim() !='') {
                                var html_tier_price = '<div id="tier-price-'+ product[0]+'" class="tier-price" style="display: none;">'+ info_html_child['tier_price'][product[0]] +'</div>'
                                $(this).append(html_tier_price);
                            }
                        }
                    }

                    if (typeof info_html_child['is_in_stock'][product[0]] != 'undefined' ) {
                        var tmpl = '';
                        if (info_html_child['is_in_stock'][product[0]] == '1') {
                            var stock = mageTemplate('#instock-mt');
                            tmpl = stock({
                                data: {
                                    id: product[0],
                                    qty: info_html_child['qty'][product[0]]
                                }
                            });
                        }
                        if (info_html_child['is_in_stock'][product[0]] == '0') {
                            var outofstock = mageTemplate('#outofstock-mt');
                            tmpl = outofstock({
                                data: {
                                    id: product[0]
                                }
                            });
                        }
                        console.log(info_html_child);
                        $(this).append(tmpl);
                    }

                    if ($.inArray(product[0], info_html_child['product_allow']) != -1) {
                        $(this).find('.qty').removeAttr('disabled')
                        $(this).find('.ui-spinner-button').css('visibility','')
                    } else {
                        $(this).find('.qty').attr('disabled','disabled')
                        $(this).find('.ui-spinner-button').css('visibility','hidden')
                    }

                } else {
                    $(this).find('.qty').attr('disabled','disabled')
                    $(this).find('*').css('visibility','hidden')
                }
            })
        },

        _painDiagonalLine: function () {
            var hadRenderLine = $('.render-diagonal-line').attr('value');
            if ($('.label-attribute-f').length && hadRenderLine != "1") {
                var att1height = $('.label-attribute-f .label-attribute1').outerHeight() + 10;
                var he = $('.label-attribute-f').outerHeight() + att1height;
                $('.label-attribute-f .label-attribute0').css('margin-top', att1height);
                $('.label-attribute-f .label-attribute1').css('position', "relative");
                $('.label-attribute-f .label-attribute1').css('left', "-5px");
                var wi = $('.label-attribute-f').outerWidth();
                /* calculate width with overflow */
                if ($('.label-attribute-f')[0].scrollWidth > $('.label-attribute-f').innerWidth()) {
                    wi = $('.label-attribute-f')[0].scrollWidth + 20;
                    $('.label-attribute-f').css('width', wi);
                    $('.label-attribute-f .label-attribute1').css('left', "-15px");
                }

                $('#bsscanvas').attr('width', wi);
                $('#bsscanvas').attr('height',he);
                var c = document.getElementById("bsscanvas");
                var ctx = c.getContext("2d");
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.lineTo(wi, he);
                ctx.lineWidth = 1;
                ctx.strokeStyle = '#ccc';
                ctx.stroke();
                $('#bsscanvas').appendTo($('.label-attribute-f'));
                $('.render-diagonal-line').attr('value', '1');
            }
        },

        _EventListener: function () {
            var $widget = this;
            // hover box qty
            $('.child-product-matrix .qty').focusin(function(event){
                if ($('.block-bss-matrixview').get(0).scrollHeight == $('.block-bss-matrixview').innerHeight() && $('.block-bss-matrixview').get(0).scrollWidth == $('.block-bss-matrixview').innerWidth()) {
                    $('.block-bss-matrixview').css('overflow','inherit')
                }
                $widget.options.zIndex = $(this).parents('td').css('zIndex');
                $(this).parents('td').css('zIndex', '999');
                $widget._styleTooltipBeforeshow($(this));
            });
            // hide tier price + error
            $('.child-product-matrix .qty').focusout(function(){
                if ($('.block-bss-matrixview').get(0).scrollHeight > $('.block-bss-matrixview').innerHeight() || $('.block-bss-matrixview').get(0).scrollWidth > $('.block-bss-matrixview').innerWidth()) {
                    $('.block-bss-matrixview').css('overflow','auto')
                }
                $(this).parents('td').css('zIndex', $widget.options.zIndex);
                $widget._hideTooltip($(this));
            });

            // reset matrix view
            $(document).on('click','#reset-matrix-view',function(){
                $widget._Reset();
            })

            // select option
            $('input.super-attribute-select,select.super-attribute-select,textarea.super-attribute-select').on('change paste keyup', function(){
                var attribute_id = $(this).attr('name').match(/\d+/)[0];
                var option_value = $(this).val();
                if (option_value) {}
                $('#bss-matrixview .rm-attribute').each(function() {
                    if ($(this).data('attribute-id') == attribute_id) {
                        $(this).remove();
                    }
                });

                $('#bss-matrixview input.child-product,#bss-matrixview .price-box,#bss-matrixview .tier-price,#bss-matrixview .stock-st').remove();

                var attribute_select = false;
                $('#bss-matrixview .child-product-matrix').each(function() {
                    $(this).find('.super_attribute_matrix').each(function() {
                        if ($(this).val() == option_value && $(this).attr('data-attribute-id') == attribute_id) {
                            attribute_select = true;
                            return false;
                        }
                    });
                })

                if (!attribute_select) {
                    $('#bss-matrixview .child-product-matrix').each(function(){
                        $(this).append('<input type="hidden" disabled="disabled" class="super_attribute_matrix rm-attribute" data-attribute-id="'+ attribute_id +'" value="'+ option_value+ '" />');
                    })
                    $widget._Reset(true);
                    setTimeout(function() {
                        $widget._painDiagonalLine();
                    },100)
                }

                $widget._RenderProduct();

                // add qty
                var product_qtys = $widget.options.product_qtys;
                if (product_qtys.length > 0) {
                    for (var i = 0; i < product_qtys.length; i++) {
                        $('input[value="' + product_qtys[i].productId + '"].child-product').parent().find('.qty').val(product_qtys[i].qty);
                    }
                    $('#bss-matrixview .qty').trigger('change');
                }
            })

            $('#bss-matrixview .qty,.ui-spinner-button,input.product-custom-option,select.product-custom-option ,textarea.product-custom-option').on("change paste keyup click", function(event){
                setTimeout(function() {
                    var total_price = $widget.getTotalPrice(),
                        amount = 0;
                    if (parseInt($widget.options.price_display_type) == 3) {
                        amount = total_price * (1 - 1 / (1 + parseFloat($widget.options.taxRate)));
                        var total_price_ex = total_price - amount;
                        $('.total-price-matrix').text($widget.getFormattedPrice(total_price) + '  ' + '(' + $widget.options.excl_text + ':'+ $widget.getFormattedPrice(total_price_ex) + ')');
                    }else{
                        $('.total-price-matrix').text($widget.getFormattedPrice(total_price));
                        var totalChildProductsQty = 0;
                        var parentId = $('input[name=product]').val();
                        $('#bss-matrixview input.qty').each(function(){
                            if ($(this).val() !='' && $(this).val() > 0) {
                                var childProducsCheck = $(this).attr('id').split('_');
                                var childProducId = childProducsCheck[2];
                                var unit_price_hemming = parseFloat($('#product-price-'+childProducId).attr('data-price-amount'));
                                var custmizedMatrix = $('#set_hidden_valus_popup_'+childProducId).val();
                                if (typeof(custmizedMatrix) != "undefined" && custmizedMatrix != ''){
                                    var priceHemming = parseFloat($('#custom_options_qty_'+parentId+'_'+childProducId+'_0').attr('data-pricehemming'));
                                    var unit_price_hemming = parseFloat(priceHemming+unit_price_hemming);
                                }
                                totalChildProductsQty += parseFloat($(this).val()*unit_price_hemming);
                            }
                        });
                        $('.total-price-matrix').html("&#36;"+totalChildProductsQty.toFixed(2));
                    }
                },100)

                $widget.saveProductsqty($(this));
            })


            // custom product out of stock notify
            $(document).on('click','.product-alert-checkbox',function() {
                var productId = $(this).parents('td').find('.child-product').val();
                if ($(this).is(':checked')) {
                    $('#stockalert-form .control').append('<input type="hidden" id="product-alert-'+ productId +'" name="product_id[]" value="'+ productId +'">');
                } else {
                    $('#product-alert-'+ productId +'').remove()
                }
            })

            $(document).on("submit", "#stockalert-form", function(e){
                if ($('#stockalert-form input[name="product_id[]"]').length < 1) {
                    alert($.mage.__('Please select product !'));
                    return false;
                }
            });

            $(document).on('click','.cancel-stock-alert',function() {
                var productId = $(this).parents('td').find('.child-product').val();
                $('#product-alert-stop-notify input[name="product_id"]').val(productId);
                $('#product-alert-stop-notify').submit();
            })
        },

        _showTooltip: function($this, posY, posX, posY_minus, posX_minus) {
            if ($this.parents('.child-product-matrix').find('.mess-err-bss').text() != '') {
                var top = posY - posY_minus - 10 - $this.parents('.child-product-matrix').find('.mess-err-bss-m').outerHeight();
                var left =  posX + $('.custom-button-bss').width() + 20 - posX_minus -($this.parents('.child-product-matrix').find('.mess-err-bss-m').outerWidth()/2);
                $this.parents('.child-product-matrix').find('.mess-err-bss-m').show().css({'top':top,'left':left});
            } else {
                if($this.parents('.child-product-matrix').find('.tier-price').length){
                    var top = posY - posY_minus - 10 - $this.parents('.child-product-matrix').find('.tier-price').outerHeight();
                    var left = posX + $('.custom-button-bss').width() + 10 - posX_minus -($this.parents('.child-product-matrix').find('.tier-price').outerWidth()/2);
                    $this.parents('.child-product-matrix').find('.tier-price').show().css({'top':top,'left':left});
                }
            }
        },

        _hideTooltip: function($this) {
            $this.parents('.child-product-matrix').css('zIndex', '9');
            $this.parents('.child-product-matrix').find('.tier-price').hide();
            $this.parents('.child-product-matrix').find('.mess-err-bss-m').hide();
        },

        _styleTooltipBeforeshow: function($this) {
            var $widget = this;
            var closestRelativeParent = $('.child-product-matrix').parents().filter(function() {
                var $prelative = $(this);
                return $prelative.is('body') || $prelative.css('position') == 'relative';
            }).slice(0,1);
            var posX_minus = $(closestRelativeParent).offset().left;
            var posY_minus = $(closestRelativeParent).offset().top;
            if ($this.parents('td').css('position') == 'relative') {
                posX_minus = $this.parents('td').offset().left;
                posY_minus = $this.parents('td').offset().top;
            }
            var posX = $this.offset().left;
            var posY = $this.offset().top;
            $widget._showTooltip($this, posY, posX, posY_minus, posX_minus);
        },

        _Reset: function(change_attribute) {
            if (!change_attribute) {
                this.options.product_qtys = [];
            }
            $('.child-product-matrix .qty').val(0);
            $('.custom_options_hemming_qty').val('');
            $('.set_hidden_valus_popup').val('');
            $('.total-price-matrix').text(this.getFormattedPrice(0));
        },

        saveProductsqty: function($this) {
            var $widget = this,
                product_qtys = $widget.options.product_qtys,
                productId = $this.parents('.child-product-matrix').find('.child-product').val(),
                qty = $this.parents('.child-product-matrix').find('.qty').val(),
                index = _.findKey(product_qtys, {productId: productId});
            if (index > 0 || index === 0) {
                product_qtys[index] = { 'productId': productId, 'qty': qty};
            } else {
                product_qtys.push({ 'productId': productId, 'qty': qty});
            }
            $widget.options.product_qtys = product_qtys;
        },

        getQtyofProductInCart: function() {
            var $widget = this;
            if ($widget.options.tier_price_calculate == 1) {
                $.ajax({
                    url: $widget.options.matrixview_url,
                    dataType: 'json',
                    success: function(res) {
                        if (res.product_qtys) {
                            $('#qty_of_product_in_cart').val(res.product_qtys);
                        }
                    }
                });
            }
        },

        getTotalPrice: function () {
            var $widget = this,
                info_html_child = $widget.options.info_html_child,
                tier_prices = info_html_child['tierPrices'],
                cks_tier_price = $widget.options.cks_tier_price,
                tier_price_calculate = $widget.options.tier_price_calculate,
                childproduct =  $widget.options.childproduct,
                total_qty = $widget.getTotalQty(),
                custom_price = 0,
                total_price = 0;

            if (localStorage.getItem("option_amountMVT")) {
                custom_price = parseFloat(localStorage.getItem("option_amountMVT"));
            }

            if (tier_price_calculate == 1) {
                total_qty += parseFloat($('#qty_of_product_in_cart').val());
            }

            $('#bss-matrixview .child-product-matrix').each(function() {
                var qty = 0, tier_price = 0 ;
                if ($(this).find('.qty').val() !='') {
                    qty = parseFloat($(this).find('.qty').val());
                }
                var child_product = $(this).find('input.child-product').val();
                if (child_product && qty > 0) {
                    var unit_price = parseFloat(childproduct["optionPrices"][child_product]['finalPrice']["amount"]);
                    if (!$.isEmptyObject(tier_prices) && typeof tier_prices[child_product] !== 'undefined') {
                        if (tier_price_calculate == 1 && cks_tier_price !='') {
                            tier_price = parseFloat($widget.getTierPrice(tier_prices[child_product],total_qty));
                        }else{
                            tier_price = parseFloat($widget.getTierPrice(tier_prices[child_product],qty));
                        }
                    }

                    if (tier_price > 0 &&  tier_price < unit_price ) {
                        unit_price  = tier_price;
                    }
                    total_price += (custom_price*qty);
                    total_price += (unit_price*qty);
                }
            })
            return total_price;
        },

        getTierPrice: function  (tier_prices, qty) {
            var prevQty = 1, price = 0;
            $.each( tier_prices, function(price_qty, tier_price ) {
                if (qty < tier_price["qty"]) return true;
                if (tier_price["qty"] < prevQty) return true;
                price = tier_price["price"];
                prevQty = tier_price["qty"];
            });
            return price;
        },

        getTotalQty: function () {
            var total_qty = 0;
            $('#bss-matrixview .child-product-matrix .qty').each(function() {
                if ($(this).val() !='' && $(this).val() > 0) {
                    total_qty += parseFloat($(this).val());
                }
            })
            return total_qty;
        },

        getFormattedPrice: function (price) {
            return priceUtils.formatPrice(price, this.options.priceFormat);
        }
    });
    return $.mage.ConfigurableMatrixView;
});
