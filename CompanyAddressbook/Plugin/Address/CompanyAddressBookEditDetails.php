<?php

namespace I95Dev\CompanyAddressbook\Plugin\Address;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Block\Address\Edit;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class CompanyAddressBookEditDetails
{
    protected $companymanagement;
    protected $companyContext;

    public function __construct(
        CompanyManagementInterface $companymanagement,
        LoggerInterface $logger,
        Session $customer_session,
        CustomerRepositoryInterface $customerFactory,
        CompanyContext $companyContext
    ) {
        $this->companymanagement = $companymanagement;
        $this->_logger = $logger;
        $this->customerSession = $customer_session;
        $this->customerFactory = $customerFactory;
        $this->companyContext = $companyContext;
    }

    public function afterGetCustomer(Edit $subject, $customer)
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $companyDetails = $this->companymanagement->getByCustomerId($customerId);
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if ($companyUserStatus == 1) {
            $superUserId = $companyDetails->getSuperUserId();
            return $this->customerFactory->getById($superUserId);
        } else {
            return $customer;
        }
    }

    public function afterGetTitle(Edit $subject, $title)
    {
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if ($companyUserStatus == 1) {
            return __('Address Book');
        } else {
            return $title;
        }
    }
}
