<?php

namespace Loqate\ApiIntegration\Plugin;

use Loqate\ApiIntegration\Helper\Data;
use Loqate\ApiIntegration\Helper\Extra;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Model\CountryFactory;

class ChangeAddressDefaultCountry
{
    protected $countryFactory;
    private Extra $extra;
    private Data $helper;

    public function __construct(CountryFactory $countryFactory, Extra $extra, Data $helper)
    {
        $this->countryFactory = $countryFactory;
        $this->extra = $extra;
        $this->helper = $helper;
    }

    public function afterGetCountryId(AddressInterface $subject, $result)
    {
        if (!$this->helper->getConfigValue('loqate_settings/ipcountry_settings/enable_customer_account')) {
            return $result;
        }

        $countryResult = $this->extra->ipToCountry();

        if (isset($countryResult['Iso2']) && $countryResult['Iso2'] != null) {

            $countryCode = strtoupper($countryResult['Iso2']);

            if (empty($result)) {
                try {
                    $countryModel = $this->countryFactory->create()->loadByCode($countryCode);
                    if ($countryModel->getId()) {
                        return $countryModel->getCountryId();
                    }
                } catch (\Exception) {
                    return $result;
                }
            }
        }

        return $result;
    }
}
