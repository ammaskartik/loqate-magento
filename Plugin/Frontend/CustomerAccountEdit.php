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
        if ($this->helper->getConfigValueForWebsite('loqate_settings/email_settings/enable_register')) {
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
