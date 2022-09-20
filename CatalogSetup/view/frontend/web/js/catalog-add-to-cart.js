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
    'mage/translate',
    'jquery/ui'
], function($, $t) {
    "use strict";

    $.widget('mage.bssConfigurableAddToCart', {

        options: {
            processStart: null,
            processStop: null,
            bindSubmit: true,
            minicartSelector: '[data-block="minicart"]',
            messagesSelector: '[data-placeholder="messages"]',
            productStatusSelector: '.stock.available',
            addToCartButtonSelector: '.action.tocart',
            addToCartButtonDisabledClass: 'disabled',
            addToCartButtonTextWhileAdding: '',
            addToCartButtonTextAdded: '',
            addToCartButtonTextDefault: ''
        },

        _create: function() {
            if (this.options.bindSubmit) {
                this._bindSubmit();
            }
        },

        _bindSubmit: function() {
            var self = this;
            this.element.on('submit', function(e) {
                e.preventDefault();
                self.submitForm($(this));
            });
        },

        isLoaderEnabled: function() {
            return this.options.processStart && this.options.processStop;
        },

        submitForm: function (form) {
            var addToCartButton, self = this;
            var totalqty = 0;
            $('#bss-matrixview input.qty').each(function(){
                if ($(this).val()) {
                    totalqty = parseFloat($(this).val()) + totalqty;
                }
            });

            if (totalqty == 0) {
                alert('Please enter quantity!');
                return false;
            }
            var changedQuantityArr = [];
             $('#bss-matrixview input.qty').each(function(){
                 if ($(this).val() !='' && $(this).val() > 0) {
                    var custmizedQtyCheck = $(this).attr('id').split('_');
                    var childId = custmizedQtyCheck[2];
                    if ($('#set_hidden_valus_popup_'+childId).length) {
                        var childQtyMatrix = $("#qty_hemming_"+childId).val();
                        var custmizeChildQtyMatrix = $("#set_hidden_valus_popup_"+childId).attr('data-totalqty');
                        if(childQtyMatrix != custmizeChildQtyMatrix){
                            changedQuantityArr.push(childId);
                            $("#qty_hemming_"+childId).focus();    
                            $("#qty_hemming_"+childId).css("border",'1px solid red');
                        }else{
                            changedQuantityArr.pop(childId);
                            $("#qty_hemming_"+childId).css("border",'1px solid #bbb');
                        }
                    }
                }
            });
            if(changedQuantityArr.length>0){
                return  false;
            }
            if (form.has('input[type="file"]').length && form.find('input[type="file"]').val() !== '') {
                self.element.off('submit');
                // disable 'Add to Cart' button
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);
                addToCartButton.prop('disabled', true);
                addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
                form.submit();
            } else {
                self.ajaxSubmit(form);
            }
        },

        ajaxSubmit: function(form) {
            $('.block-bss-matrixview').addClass('ajaxloadmatrixrelative');
            var height = $('#bss-matrixview').height();
            $('.block-bss-matrixview').prepend('<div class="overlayajaxloadmatrix"><div class="ajaxloadmatrix"></div></div>');
            $('.ajaxloadmatrix').css('top',(height - 40)/2);
            $('.overlayajaxloadmatrix').css('height',$('#bss-matrixview')[0].scrollHeight);
            var self = this;
            $(self.options.minicartSelector).trigger('contentLoading');
            self.disableAddToCartButton(form);
            $('#bss-matrixview').find('.qty').removeClass('bss-error');
            $('#bss-matrixview').find('.mess-err-bss').text('');
            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                type: 'post',
                dataType: 'json',
                beforeSend: function() {
                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStart);
                    }
                },
                success: function(res) {
                    $('.block-bss-matrixview').removeClass('ajaxloadmatrixrelative');
                    $('.overlayajaxloadmatrix').remove();

                    if (self.isLoaderEnabled()) {
                        $('body').trigger(self.options.processStop);
                    }

                    if (res.backUrl) {
                        window.location = res.backUrl;
                        return;
                    }

                    if (res.messages) {
                        $(self.options.messagesSelector).html(res.messages);
                    }

                    if (res.minicart) {
                        $(self.options.minicartSelector).replaceWith(res.minicart);
                        $(self.options.minicartSelector).trigger('contentUpdated');
                    }

                    if (res.product && res.product.statusText) {
                        $(self.options.productStatusSelector)
                            .removeClass('available')
                            .addClass('unavailable')
                            .find('span')
                            .html(res.product.statusText);
                    }

                    // customize display error
                    $('#bss-matrixview').find('input.child-product').each(function(){
                        if ($(this).parent().find('.ui-spinner').length) {
                            $(this).parent().find('.ui-spinner').first().removeClass('bss-error');
                        }else{
                            $(this).parent().find('.qty').first().removeClass('bss-error');
                        }
                        $(this).parent().find('.mess-err-bss').first().text();
                    })

                    if (res.errors) {
                        $.each(res.errors, function(index, item) {
                            $('#bss-matrixview').find('input.child-product').each(function(){
                                if (index == $(this).val() ) {
                                    if ($(this).parent().find('.ui-spinner').length) {
                                        $(this).parent().find('.ui-spinner').first().addClass('bss-error');
                                    }else{
                                        $(this).parent().find('.qty').first().addClass('bss-error');
                                    }
                                    $(this).parent().find('.mess-err-bss').first().text(item);
                                }
                            })
                        });
                    }

                    if (res.product_qtys) {
                        $('#qty_of_product_in_cart').val(res.product_qtys)
                    }

                    if (!$('#bss-matrixview .bss-error').length) {
                        setTimeout(function() {
                            $('#bss-matrixview .qty').val(0);
                            $('.custom_options_hemming_qty').val('');
                            $('.set_hidden_valus_popup').val('');
                            $('#bss-matrixview .qty').trigger('change')
                        }, 1000);
                    }
                    
                    // end
                    self.enableAddToCartButton(form);
                }
            });
        },

        disableAddToCartButton: function(form) {
            var addToCartButtonTextWhileAdding = this.options.addToCartButtonTextWhileAdding || $t('Adding...');
            var addToCartButton = $(form).find(this.options.addToCartButtonSelector);
            addToCartButton.addClass(this.options.addToCartButtonDisabledClass);
            addToCartButton.find('span').text(addToCartButtonTextWhileAdding);
            addToCartButton.attr('title', addToCartButtonTextWhileAdding);
        },

        enableAddToCartButton: function(form) {
            var addToCartButtonTextAdded = this.options.addToCartButtonTextAdded || $t('Added');
            var self = this,
                addToCartButton = $(form).find(this.options.addToCartButtonSelector);

            addToCartButton.find('span').text(addToCartButtonTextAdded);
            addToCartButton.attr('title', addToCartButtonTextAdded);

            setTimeout(function() {
                var addToCartButtonTextDefault = self.options.addToCartButtonTextDefault || $t('Add to Cart');
                addToCartButton.removeClass(self.options.addToCartButtonDisabledClass);
                addToCartButton.find('span').text(addToCartButtonTextDefault);
                addToCartButton.attr('title', addToCartButtonTextDefault);
            }, 1000);
        }
    });

    return $.mage.bssConfigurableAddToCart;
});