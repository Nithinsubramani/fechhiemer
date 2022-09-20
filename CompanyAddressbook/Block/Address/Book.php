<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace I95Dev\CompanyAddressbook\Block\Address;

use Magento\Framework\Exception\NoSuchEntityException;

class Book extends \Magento\Customer\Block\Address\Book
{

    public function getCurrentCustomer()
    {
        $customer = null;
        try {
            $customer = $this->currentCustomer->getCustomer();
        } catch (NoSuchEntityException $e) {
            $customer = null;
        }
        return $customer;
    }
}
