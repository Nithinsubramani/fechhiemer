<?php

namespace I95Dev\CatalogSetup\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Index extends Template
{
    // phpcs:disable
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * Get form action URL for POST booking request
     *
     * @return string
     * phpcs:disable
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
}
