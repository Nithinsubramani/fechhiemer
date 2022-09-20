<?php

namespace I95Dev\CatalogSetup\Helper;

use Bss\ConfigurableMatrixView\Helper\Data as hemmingHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends hemmingHelper
{
    const XML_PATH_HEMMING = 'i95dev_settings_hemming/';

    public function getValueAddedService()
    {
        return $this->getConfigValue(self::XML_PATH_HEMMING . 'general_hemming/Hemming_Title');
    }

    public function getDefualtAddedService()
    {
        return $this->getConfigValue(self::XML_PATH_HEMMING . 'defualt_hemming/Defualt_Title');
    }
}
