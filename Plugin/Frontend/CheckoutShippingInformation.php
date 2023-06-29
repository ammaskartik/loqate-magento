<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Framework\Exception\StateException;

/**
 * Class CheckoutShippingInformation
 */
class CheckoutShippingInformation extends AbstractPlugin
{
    /**
     * Validate shipping address information
     *
     * @throws StateException
     */
    public function aroundSaveAddressInformation(
        ShippingInformationManagement $subject,
        callable                      $proceed,
        $cartId,
        $addressInformation
    ) {
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            $proceed($cartId, $addressInformation);
        }

        if ($shippingAddress = $addressInformation->getShippingAddress()->getData()) {
            $errors = [];
            if ($this->helper->getConfigValueForWebsite('loqate_settings/address_settings/enable_checkout')) {
                $response = $this->validator->verifyAddress($shippingAddress);
                if (!empty($response['error'])) {
                    $errors[] = $response['message'];
                }
            }

            if ($this->helper->getConfigValueForWebsite('loqate_settings/phone_settings/enable_checkout')) {
                if (isset($shippingAddress['telephone'])) {
                    $errorMessage = $this->validatePhone($shippingAddress['telephone'], $shippingAddress['country_id']);
                    if ($errorMessage) {
                        $errors[] = $errorMessage;
                    }
                }
            }

            if ($this->helper->getConfigValueForWebsite('loqate_settings/email_settings/enable_checkout')
            && ($email = $this->session->getData('loqate_email_to_validate'))) {
                $errorMessage = $this->validateEmail($email);
                if ($errorMessage) {
                    $errors[] = $errorMessage;
                } else {
                    $this->session->setData('loqate_email_to_validate', '');
                }
            }

            if ($errors) {
                throw new StateException(__(implode(PHP_EOL, $errors)));
            }
        }

        return $proceed($cartId, $addressInformation);
    }
}
