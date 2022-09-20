<?php

namespace I95Dev\CompanyAddressbook\Plugin\Address;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Block\Address\Book;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class CompanyAddressBookDetails
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

    public function afterGetCustomer(Book $subject, $customer)
    {
        $customerId = $this->customerSession->getCustomer()->getId();
        $companyDetails = $this->companymanagement->getByCustomerId($customerId);
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if ($companyUserStatus == 1) {
            $superUserId = $companyDetails->getSuperUserId();
            $customer = $this->customerFactory->getById($superUserId);
            return $customer;
        } else {
            return $customer;
        }
    }
}
