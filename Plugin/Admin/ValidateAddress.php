<?php

namespace Loqate\ApiIntegration\Plugin\Admin;

use Loqate\ApiIntegration\Helper\Data;
use Loqate\ApiIntegration\Helper\Validator;
use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Customer\Controller\Adminhtml\Address\Validate;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;

/**
 * Class ValidateAddress
 */
class ValidateAddress extends AbstractPlugin
{
    /**
     * Check if address and phone are valid
     *
     * @param Validate $subject
     * @param callable $proceed
     * @return Json
     */
    public function aroundExecute(Validate $subject, callable $proceed)
    {
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            return $proceed();
        }

        $request = $subject->getRequest()->getPostValue();
        $errors = [];

        if ($this->helper->getConfigValue('loqate_settings/address_settings/enable_customer_account_admin')) {
            if (isset($request['street']['0'])) {
                $request['street_1'] = $request['street']['0'];
            }
            if (isset($request['street']['1'])) {
                $request['street_2'] = $request['street']['1'];
            }

            $response = $this->validator->verifyAddress($request);
            if (!empty($response['error'])) {
                $errors[] = $response['message'];
            }
        }

        if ($this->helper->getConfigValue('loqate_settings/phone_settings/enable_customer_account_admin')) {
            if (isset($request['telephone'])) {
                $errorMessage = $this->validatePhone($request['telephone']);
                if ($errorMessage) {
                    $errors[] = $errorMessage;
                }
            }
        }

        if ($errors) {
            $requestResponse = new DataObject();
            $resultJson = $this->resultJsonFactory->create();
            $requestResponse->setError(true);
            $requestResponse->setMessages($errors);

            $resultJson->setData($requestResponse);

            return $resultJson;
        }

        return $proceed();
    }
}
