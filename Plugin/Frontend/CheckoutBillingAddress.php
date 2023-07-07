<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Loqate\ApiIntegration\Helper\Controller;
use Loqate\ApiIntegration\Helper\Data;
use Loqate\ApiIntegration\Helper\Validator;
use Loqate\ApiIntegration\Logger\Logger;
use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Checkout\Model\ShippingInformation;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\BillingAddressManagement;
use Magento\Quote\Model\Quote;

/**
 * Class CheckoutBillingAddress
 */
class CheckoutBillingAddress extends AbstractPlugin
{
    protected $cartRepository;
    private AddressRepositoryInterface $addressRepository;

    /**
     * YourClass constructor
     *
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param Session $session
     * @param Validator $validator
     * @param Data $helper
     * @param JsonFactory $resultJsonFactory
     * @param mixed $yourProperty
     */
    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        Session $session,
        Validator $validator,
        Data $helper,
        JsonFactory $resultJsonFactory,
        CartRepositoryInterface $cartRepository,
        AddressRepositoryInterface $addressRepository,
        Logger $logger,

    ) {
        parent::__construct($context, $urlBuilder, $session, $validator, $helper, $resultJsonFactory);
        $this->cartRepository = $cartRepository;
        $this->addressRepository = $addressRepository;
        $this->logger = $logger;
    }

    /**
     * Validate billing address information
     *
     * @throws InputException
     */
    public function aroundAssign(
        BillingAddressManagement $subject,
        callable                      $proceed,
        $cartId,
        $address,
        $useForShipping = false
    ) {
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            $proceed($cartId, $address, $useForShipping);
        }

        if ($billingAddress = $address->getData()) {
            $errors = [];

            if ($this->helper->getConfigValueForWebsite('loqate_settings/address_settings/enable_checkout')) {
                $response = $this->validator->verifyAddress($billingAddress);
                if (!empty($response['error'])) {
                    $errors[] = $response['message'];
                }
            }

            if ($this->helper->getConfigValueForWebsite('loqate_settings/phone_settings/enable_checkout')) {
                if (isset($billingAddress['telephone'])) {
                    $errorMessage = $this->validatePhone($billingAddress['telephone'], $billingAddress['country_id']);
                    if ($errorMessage) {
                        $errors[] = $errorMessage;
                    }
                }
            }

            if ($errors) {
                $this->session->setData('loqate_billing_errors', true);
                throw new InputException(__(implode(PHP_EOL, $errors)));
            }
            $this->session->setData('loqate_billing_errors', false);
        }

        return $proceed($cartId, $address, $useForShipping);
    }

//todo remove this or save custom attributes from request onto quote
//    public function afterAssign(
//        $subject,
//        $addressId,
//        $cartId,
//        $useForShipping = false
//    ) {
//        /** @var Quote $quote */
//        $quote = $this->cartRepository->get($cartId);
//        $billingAddress = $quote->getBillingAddress();
//
//        $customerAddressId = $billingAddress->getCustomerAddressId();
//        $customerAddress = $this->addressRepository->getById($customerAddressId);
//
//        for ($i = 1; $i <= Controller::MAX_DATA_SETS_FIELDS; $i++) {
//            $field = "loqate_field{$i}_format";
//            $customAttribute = $customerAddress->getCustomAttribute($field);
//            if (!empty($customAttribute)) {
//                $billingAddress->setData($field, $customAttribute->getValue());
//            }
//        }
//        $billingAddress->save();
//    }
}
