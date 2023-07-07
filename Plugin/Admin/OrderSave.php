<?php

namespace Loqate\ApiIntegration\Plugin\Admin;

use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Controller\Adminhtml\Order\Create\Save;

/**
 * OrderSave class
 */
class OrderSave extends AbstractPlugin
{
    /**
     * Check if address, email and phone number are valid on order create
     *
     * @param Save $subject
     * @param callable $proceed
     * @return Redirect
     */
    public function aroundExecute(Save $subject, callable $proceed)
    {
        //todo admin create new order with new addresses - info saved in quote
        //todo at create new order trigger click once to add new and then to previous selection
        //to load the enhanced fields values
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            return $proceed();
        }

        $request = $subject->getRequest()->getPostValue();
        $error = false;
        $requestAddresses = [];
        if (isset($request['order']['billing_address'])) {
            $requestAddresses['billing_address'] = $request['order']['billing_address'];
        }

        if (isset($request['order']['shipping_address'])) {
            $requestAddresses['shipping_address'] = $request['order']['shipping_address'];
        }

        if ($this->helper->getConfigValue('loqate_settings/address_settings/enable_create_order_admin')) {
            foreach ($requestAddresses as &$requestAddress) {
                if (isset($requestAddress['street']['0'])) {
                    $requestAddress['street_1'] = $requestAddress['street']['0'];
                }
                if (isset($requestAddress['street']['1'])) {
                    $requestAddress['street_2'] = $requestAddress['street']['1'];
                }
            }

            //validate addresses
            $response = $this->validator->verifyMultipleAddresses($requestAddresses, true);
            if (is_array($response)) {
                foreach ($response as $key => $addressResponse) {
                    if (!$addressResponse) {
                        $error = true;
                        $this->messageManager->addErrorMessage(
                            __('The provided address is invalid: ') . '#' . $key
                        );
                    }
                }
            } else {
                $error = true;
                $this->messageManager->addErrorMessage(
                    __('An unexpected error occurred while trying to validate your address.')
                );
            }
        }

        if ($this->helper->getConfigValue('loqate_settings/phone_settings/enable_create_order_admin')) {
            //validate phone numbers for each address
            foreach ($requestAddresses as $key => $address) {
                if (isset($address['telephone'])) {
                    $errorMessage = $this->validatePhone($address['telephone'], $address['country_id']);
                    if ($errorMessage) {
                        $error = true;
                        $this->messageManager->addErrorMessage("#$key: " . $errorMessage);
                    }
                }
            }
        }

        if ($this->helper->getConfigValue('loqate_settings/email_settings/enable_create_order_admin')) {
            //validate email address
            if (isset($request['order']['account']['email'])) {
                $errorMessage = $this->validateEmail($request['order']['account']['email']);
                if ($errorMessage) {
                    $error = true;
                    $this->messageManager->addErrorMessage($errorMessage);
                }
            }
        }

        if ($error) {
            $this->session->setCustomerFormData($request);
            return $this->resultRedirectFactory->create()->setUrl(
                $this->redirect->error($this->redirect->getRefererUrl())
            );
        }

        return $proceed();
    }
}
