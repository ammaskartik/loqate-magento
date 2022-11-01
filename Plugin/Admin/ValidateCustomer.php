<?php

namespace Loqate\ApiIntegration\Plugin\Admin;

use Loqate\ApiIntegration\Helper\Data;
use Loqate\ApiIntegration\Helper\Validator;
use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Customer\Controller\Adminhtml\Index\Validate;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;

/**
 * Class ValidateCustomer
 */
class ValidateCustomer extends AbstractPlugin
{
    /**
     * Check if email address is valid
     *
     * @param Validate $subject
     * @param callable $proceed
     * @return Json
     */
    public function aroundExecute(Validate $subject, callable $proceed)
    {
        if ($this->helper->getConfigValue('loqate_settings/email_settings/enable_customer_account_admin')) {
            $request = $subject->getRequest()->getPostValue();

            if (isset($request['customer']['email'])) {
                $errorMessage = $this->validateEmail($request['customer']['email']);
                if ($errorMessage) {
                    $requestResponse = new DataObject();
                    $resultJson = $this->resultJsonFactory->create();
                    $requestResponse->setError(true);
                    $requestResponse->setMessages([$errorMessage]);

                    $resultJson->setData($requestResponse);

                    return $resultJson;
                }
            }
        }

        return $proceed();
    }
}
