<?php

namespace Loqate\ApiIntegration\Plugin;


use Loqate\ApiIntegration\Helper\Controller;

class HideEnhancedFieldsCheckoutPlugin
{
    public function __construct(
        \Magento\Payment\Model\Config $paymentModelConfig
    ) {
        $this->paymentModelConfig = $paymentModelConfig;
    }

    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {

        for ($i = 1; $i <= Controller::MAX_DATA_SETS_FIELDS; $i++) {
            $fieldName = "loqate_field{$i}_format";

            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$fieldName])) {
                $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$fieldName]['visible'] = false;
            }


            $activePayments = $this->paymentModelConfig->getActiveMethods();
            if (count($activePayments)) {
                foreach ($activePayments as $paymentCode => $payment) {
                    if (isset(
                        $jsLayout['components']['checkout']['children']['steps']['children']
                        ['billing-step']['children']['payment']['children']
                        ['payments-list']['children'][$paymentCode.'-form']['children']
                        ['form-fields']['children'][$fieldName]['visible']
                    )) {
                        $jsLayout['components']['checkout']['children']['steps']['children']
                        ['billing-step']['children']['payment']['children']
                        ['payments-list']['children'][$paymentCode.'-form']['children']
                        ['form-fields']['children'][$fieldName]['visible'] = false;
                    }
                }
            }
        }

        return $jsLayout;
    }
}

