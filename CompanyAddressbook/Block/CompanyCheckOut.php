<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace I95Dev\CompanyAddressbook\Block;

use Magento\Checkout\Block\Onepage;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Company\Model\CompanyContext;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Element\Template\Context;

/**
 * Check Out block
 *
 * @api
 * @since 100.0.2
 */
class CompanyCheckOut extends Onepage
{
    public $companyContext;

    public function __construct(
        Context $context,
        FormKey $formKey,
        CompositeConfigProvider $configProvider,
        CompanyContext $companyContext,
        array $data = []
    ) {
        parent::__construct($context, $formKey, $configProvider, $data);
        $this->companyContext = $companyContext;
    }

    public function companyDetailsList()
    {
        return $this->companyContext->isCurrentUserCompanyUser();
    }
}
