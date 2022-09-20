<?php

/**
 * Author: I95Dev
 * Description: Dispalying company admin address for company users
 *
 */

namespace I95Dev\CompanyAddressbook\Plugin\Company\Users;

use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use \Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\CustomerAddressDataProvider;

/**
 * Add company order extension attribute to order grid collection.
 */
class CompanyAddress
{
    protected $companyContext;

    public function __construct(
        CompanyManagementInterface $companyManagement,
        CustomerRepositoryInterface $customerRepository,
        CompanyContext $companyContext
    ) {
        $this->companyManagement = $companyManagement;
        $this->customerRepository = $customerRepository;
        $this->companyContext = $companyContext;
    }

    public function beforeGetAddressDataByCustomer(
        CustomerAddressDataProvider $subject,
        CustomerInterface $customer
    ): array {
        $company = $this->companyManagement->getByCustomerId($customer->getID());
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if ($companyUserStatus == 1) {
            $companyAdmin = $this->companyManagement->getAdminByCompanyId($company->getId());
            $customer = $this->customerRepository->getById($companyAdmin->getID());
        }

        return [$customer];
    }
}
