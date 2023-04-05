<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Model\BillingAddressManagement;

/**
 * Class CheckoutBillingAddress
 */
class CheckoutBillingAddress extends AbstractPlugin
{
    /**
     * Validate billing address information
     *
     * @throws InputException
     */
    public function aroundAssign(
        BillingAddressManagement $subject,
        callable                      $proceed,
        $cartId,
        $address,
        $useForShipping = false
    ) {
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            $proceed($cartId, $address, $useForShipping);
        }

        if ($billingAddress = $address->getData()) {
            $errors = [];

            if ($this->helper->getConfigValueForWebsite('loqate_settings/address_settings/enable_checkout')) {
                $response = $this->validator->verifyAddress($billingAddress);
                if (!empty($response['error'])) {
                    $errors[] = $response['message'];
                }
            }

            if ($this->helper->getConfigValueForWebsite('loqate_settings/phone_settings/enable_checkout')) {
                if (isset($billingAddress['telephone'])) {
                    $errorMessage = $this->validatePhone($billingAddress['telephone']);
                    if ($errorMessage) {
                        $errors[] = $errorMessage;
                    }
                }
            }

            if ($errors) {
                $this->session->setData('loqate_billing_errors', true);
                throw new InputException(__(implode(PHP_EOL, $errors)));
            }
            $this->session->setData('loqate_billing_errors', false);
        }

        return $proceed($cartId, $address, $useForShipping);
    }
}
