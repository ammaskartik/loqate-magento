<?php

namespace Loqate\ApiIntegration\Plugin;

use Loqate\ApiIntegration\Helper\Data;
use Loqate\ApiIntegration\Helper\Extra;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Directory\Model\CountryFactory;

class ChangeCheckoutDefaultCountry
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

    public function afterProcess(
        LayoutProcessorInterface $subject,
        array $jsLayout
    ) {
        if (!$this->helper->getConfigValue('loqate_settings/ipcountry_settings/enable_checkout')) {
            return $jsLayout;
        }

        $countryResult = $this->extra->ipToCountry();

        if (isset($countryResult['Iso2']) && $countryResult['Iso2'] != null) {

            $countryCode = strtoupper($countryResult['Iso2']);

            try {
                $countryModel = $this->countryFactory->create()->loadByCode($countryCode);
                if ($countryModel->getId()) {
                    $shippingAddressPath = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                    ['children']['shippingAddress']['children']['shipping-address-fieldset']['children'];
                    $shippingAddressPath['country_id']['value'] = $countryCode;

                    $billingAddressPath = &$jsLayout['components']['checkout']['children']['steps']['children']
                    ['billing-step']['children']['payment']['children']
                    ['payments-list']['children']['checkmo-form']['children']
                    ['form-fields']['children'];

                    $billingAddressPath['country_id']['value'] = $countryCode;
                }
            } catch (\Exception) {
                return $jsLayout;
            }
        }

        return $jsLayout;
    }
}
