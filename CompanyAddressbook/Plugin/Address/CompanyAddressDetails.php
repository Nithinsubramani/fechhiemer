<?php

namespace I95Dev\CompanyAddressbook\Plugin\Address;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Block\Address\Grid;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;

class CompanyAddressDetails
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

    public function afterGetCustomer(Grid $subject, $customer_id)
    {
        $level = 'DEBUG';
        $customerId = $this->customerSession->getCustomer()->getId();
        $companyDetails = $this->companymanagement->getByCustomerId($customerId);
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if ($companyUserStatus == 1) {
            $comapanyId = $companyDetails->getId();
            $superUserId = $companyDetails->getSuperUserId();
            return $this->customerFactory->getById($superUserId);
        } else {
            return $customer_id;
        }
    }
}
