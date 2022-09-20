<?php

/**
 * Author: I95Dev
 * Description: Dispalying company admin address for company users
 *
 */

namespace I95Dev\CompanyAddressbook\Model;

use I95DevConnect\MessageQueue\Helper\Data;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\CompanyContext;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteAddressValidator;

class CompanyQuoteAddressValidator extends QuoteAddressValidator
{
    protected $companyContext;
    /**
     * @var Data
     */
    protected $data;

    /**
     *
     * @param Data $data
     */
    public function __construct(
        Data $data,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        CompanyManagementInterface $companyManagement,
        CompanyContext $companyContext
    ) {
        $this->data = $data;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->companyManagement = $companyManagement;
        $this->companyContext = $companyContext;
    }

    private function doValidate(AddressInterface $address, ?int $customerId): void
    {
        //validate customer id
        if ($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            if (!$customer->getId()) {
                throw new NoSuchEntityException(
                    __('Invalid customer id %1', $customerId)
                );
            }
        }

        if ($address->getCustomerAddressId()) {
            //Existing address cannot belong to a guest
            if (!$customerId) {
                throw new NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
            //Validating address ID
            try {
                $this->addressRepository->getById($address->getCustomerAddressId());
            } catch (NoSuchEntityException $e) {
                throw new NoSuchEntityException(
                    __('Invalid address id %1', $address->getId())
                );
            }
            //Finding available customer's addresses
            $applicableAddressIds = array_map(function ($address) {
                /** @var \Magento\Customer\Api\Data\AddressInterface $address */
                return $address->getId();
            }, $this->customerRepository->getById($customerId)->getAddresses());
            if (!in_array($address->getCustomerAddressId(), $applicableAddressIds)) {
                throw new NoSuchEntityException(
                    __('Invalid customer address id %1', $address->getCustomerAddressId())
                );
            }
        }
    }

    public function validateForCart(CartInterface $cart, AddressInterface $address): void
    {

        $Cid = $this->customerSession->getCustomerId();
        if ($Cid == "") {
            return;
        }

        if ($this->data->getGlobalValue('i95_observer_skip')) {
            return;
        }
        $company = $this->companyManagement->getByCustomerId($this->customerSession->getCustomerId());
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser();
        if (isset($company) && $companyUserStatus == 1) {
            $companyAdmin = $this->companyManagement->getAdminByCompanyId($company->getId());
            $this->doValidate($address, $companyAdmin->getId());
        } else {
            $this->doValidate($address, $cart->getCustomerIsGuest() ? null : $cart->getCustomer()->getId());
        }
    }
}
