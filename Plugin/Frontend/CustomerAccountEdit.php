<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Magento\Customer\Controller\Account\EditPost;
use Magento\Framework\Controller\Result\Redirect;
use Loqate\ApiIntegration\Plugin\AbstractPlugin;

/**
 * CustomerAccountEdit class
 */
class CustomerAccountEdit extends AbstractPlugin
{
    /**
     * Check if the email is valid on customer update
     *
     * @param EditPost $subject
     * @param callable $proceed
     * @return Redirect
     */
    public function aroundExecute(EditPost $subject, callable $proceed)
    {
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            return $proceed();
        }

        if ($this->helper->getConfigValue('loqate_settings/email_settings/enable_register')) {
            $request = $subject->getRequest()->getPostValue();

            if (isset($request['email'])) {
                $errorMessage = $this->validateEmail($request['email']);
                if ($errorMessage) {
                    $this->messageManager->addErrorMessage($errorMessage);

                    $this->session->setCustomerFormData($request);
                    return $this->resultRedirectFactory->create()->setUrl(
                        $this->redirect->error($this->redirect->getRefererUrl())
                    );
                }
            }
        }

        return $proceed();
    }
}
