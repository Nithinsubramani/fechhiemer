<?php

namespace I95Dev\CompanyAddressbook\Plugin\Model;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyContext;
use \Magento\Customer\Model\Session;

class AccountManagement
{
    protected $companymanagement;
    protected $companyContext;

    public function __construct(
        CompanyManagementInterface $companymanagement,
        CompanyContext $companyContext
    ) {
        $this->companymanagement = $companymanagement;
        $this->companyContext = $companyContext;
    }

    public function beforeGetDefaultBillingAddress(\Magento\Customer\Model\AccountManagement $subject, $customer_id)
    {
        $companyDetails = $this->companymanagement->getByCustomerId($customer_id);
        //$companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if (isset($companyDetails))//&& $companyUserStatus == 1)
        {
            return $companyDetails->getSuperUserId();
        } else {
            return $customer_id;
        }
    }

    public function beforeGetDefaultShippingAddress(\Magento\Customer\Model\AccountManagement $subject, $customer_id)
    {
        $companyDetails = $this->companymanagement->getByCustomerId($customer_id);
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if (isset($companyDetails) && $companyUserStatus == 1) {
            return $companyDetails->getSuperUserId();
        } else {
            return $customer_id;
        }
    }
}
