<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Magento\Customer\Controller\Account\CreatePost;
use Magento\Framework\Controller\Result\Redirect;
use Loqate\ApiIntegration\Plugin\AbstractPlugin;

/**
 * CustomerAccountCreate class
 */
class CustomerAccountCreate extends AbstractPlugin
{
    /**
     * Check if the email is valid on customer create
     *
     * @param CreatePost $subject
     * @param callable $proceed
     * @return Redirect
     */
    public function aroundExecute(CreatePost $subject, callable $proceed)
    {
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            return $proceed();
        }

        if ($this->helper->getConfigValueForWebsite('loqate_settings/email_settings/enable_customer_account')) {
            $request = $subject->getRequest()->getPostValue();

            if (isset($request['email'])) {
                $errorMassage = $this->validateEmail($request['email']);
                if ($errorMassage) {
                    $this->messageManager->addErrorMessage($errorMassage);

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
