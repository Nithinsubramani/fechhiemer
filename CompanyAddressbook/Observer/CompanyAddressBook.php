<?php

namespace I95Dev\CompanyAddressbook\Observer;

use Magento\Company\Model\CompanyContext;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class CompanyAddressBook implements ObserverInterface
{
    /**
     * Order Model
     *
     * @var CompanyContext $companyContext
     */
    protected $companyContext;

    public function __construct(CompanyContext $companyContext, LoggerInterface $logger)
    {
        $this->_logger = $logger;
        $this->companyContext = $companyContext;
    }

    public function execute(Observer $observer)
    {
        $companyUserStatus = $this->companyContext->isCurrentUserCompanyUser(); /* Checking Company Customer or not */
        $layout = $observer->getLayout();
        $handle = '';
        if ($companyUserStatus == 1) {
            $getHandlesArray = $layout->getUpdate()->getHandles();
            if (!empty($getHandlesArray)) {
                foreach ($getHandlesArray as $handleArray) {
                    $handle = $handleArray;
                }
            }
            if ($handle == 'customer_address_index' || $handle == 'customer_address_form') {
                $layout->getUpdate()->addHandle('custom_address_book');  /* Loading Layout for Address Book */
            } elseif ($handle == 'customer_account_index') {
                $layout->getUpdate()->addHandle('custom_dashboard_address_book'); /* Loading Layout for Dashboard */
            } elseif ($handle == 'checkout_index_index') {
                $layout->getUpdate()->addHandle('custom_checkout');
            }
        }
    }
}
