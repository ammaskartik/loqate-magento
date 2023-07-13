<?php

namespace Loqate\ApiIntegration\Plugin;

use Loqate\ApiIntegration\Helper\Extra;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Directory\Model\CountryFactory;

class ChangeAddressDefaultCountry
{
    protected $countryFactory;
    private Extra $extra;

    public function __construct(CountryFactory $countryFactory, Extra $extra)
    {
        $this->countryFactory = $countryFactory;
        $this->extra = $extra;
    }

    public function afterGetCountryId(AddressInterface $subject, $result)
    {
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
