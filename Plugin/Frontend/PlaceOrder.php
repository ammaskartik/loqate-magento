<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\CouldNotSaveException;

/**
 * Class PlaceOrder
 */
class PlaceOrder
{
    /** @var Session */
    private $session;

    /**
     * PlaceOrder constructor
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Because the billing address is resubmitted at place order, check again if the customer solved the errors if any
     * @throws CouldNotSaveException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement $subject,
        $cartId,
        $paymentMethod,
        $billingAddress = null
    ) {
        if ($this->session->getData('loqate_billing_errors')) {
            throw new CouldNotSaveException(__('Please check the error again before continuing.'));
        }

        return [$cartId, $paymentMethod, $billingAddress];
    }
}
