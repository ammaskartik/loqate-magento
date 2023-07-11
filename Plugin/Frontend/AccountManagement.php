<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Customer\Model\AccountManagement as CoreAccountManagement;

/**
 * Class AccountManagement
 */
class AccountManagement extends AbstractPlugin
{
    /**
     * Store guest email address, so it can be later checked if valid
     */
    public function beforeIsEmailAvailable(
        CoreAccountManagement $subject,
        $customerEmail,
        $websiteId = null
    ) {
        if ($this->helper->getConfigValue('loqate_settings/email_settings/enable_checkout')) {
            if ($customerEmail) {
                $this->session->setData('loqate_email_to_validate', $customerEmail);
            }
        }

        return [$customerEmail, $websiteId];
    }
}
