<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Magento\Customer\Controller\Address\FormPost;
use Magento\Framework\Controller\Result\Redirect;
use Loqate\ApiIntegration\Plugin\AbstractPlugin;

/**
 * CustomerAccountAddress class
 */
class CustomerAccountAddress extends AbstractPlugin
{
    /**
     * Check if the provided address is valid
     *
     * @param FormPost $subject
     * @param callable $proceed
     * @return Redirect
     */
    public function aroundExecute(FormPost $subject, callable $proceed)
    {
        $request = $subject->getRequest()->getPostValue();

        if ($this->helper->getConfigValueForWebsite('loqate_settings/address_settings/enable_customer_account')) {
            if (isset($request['street']['0'])) {
                $request['street_1'] = $request['street']['0'];
            }
            if (isset($request['street']['1'])) {
                $request['street_2'] = $request['street']['1'];
            }

            $response = $this->validator->verifyAddress($request);
            if ($response['error']) {
                $error = true;
                $this->messageManager->addErrorMessage($response['message']);
            }
        }

        if ($this->helper->getConfigValueForWebsite('loqate_settings/phone_settings/enable_customer_account')) {
            if (isset($request['telephone'])) {
                $errorMassage = $this->validatePhone($request['telephone']);
                if ($errorMassage) {
                    $error = true;
                    $this->messageManager->addErrorMessage($errorMassage);
                }
            }
        }

        if (isset($error)) {
            $this->session->setAddressFormData($request);
            return $this->resultRedirectFactory->create()->setUrl(
                $this->redirect->error($this->redirect->getRefererUrl())
            );
        }


        return $proceed();
    }
}
