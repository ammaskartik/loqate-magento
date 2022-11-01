<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Magento\Checkout\Model\GuestPaymentInformationManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class PlaceOrderGuest
 */
class PlaceOrderGuest
{
    /** @var Session */
    private $session;

    /**
     * PlaceOrderGuest constructor
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Because the billing address is resubmitted at place order, check again if the customer solved the errors if any
     *
     * @throws CouldNotSaveException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        GuestPaymentInformationManagement $subject,
        $cartId,
        $email,
        $paymentMethod,
        $billingAddress = null
    ) {
        if ($this->session->getData('loqate_billing_errors')) {
            throw new CouldNotSaveException(__('Please check the error again before continuing.'));
        }

        return [$cartId, $email, $paymentMethod, $billingAddress];
    }
}
