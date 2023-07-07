<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Loqate\ApiIntegration\Helper\Controller;
use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Class PlaceOrder
 */
class PlaceOrder
{
    /** @var Session */
    private $session;
    protected $cartRepository;
    private AddressRepositoryInterface $addressRepository;
    /**
     * PlaceOrder constructor
     *
     * @param Session $session
     */
    public function __construct(
        Session $session,
        CartRepositoryInterface $cartRepository,
        AddressRepositoryInterface $addressRepository
    ) {
        $this->session = $session;
        $this->cartRepository = $cartRepository;
        $this->addressRepository = $addressRepository;
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

//todo remove this or save custom attributes from request onto quote
//    public function afterSavePaymentInformationAndPlaceOrder(
//        PaymentInformationManagement $subject,
//        $result,
//        $cartId,
//        $paymentMethod,
//        $billingAddress = null
//    ) {
//        /** @var Quote $quote */
//        $quote = $this->cartRepository->get($cartId);
//        $billingAddressLoqate = $quote->getBillingAddress();
//
//        $customerAddressId = $billingAddressLoqate->getCustomerAddressId();
//        $customerAddress = $this->addressRepository->getById($customerAddressId);
//
//        for ($i = 1; $i <= Controller::MAX_DATA_SETS_FIELDS; $i++) {
//            $field = "loqate_field{$i}_format";
//            $customAttribute = $customerAddress->getCustomAttribute($field);
//            if (!empty($customAttribute)) {
//                $billingAddressLoqate->setData($field, $customAttribute->getValue());
//            }
//        }
//        $billingAddressLoqate->save();
//
//        return $result;
//    }
}
