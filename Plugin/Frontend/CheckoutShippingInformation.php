<?php

namespace Loqate\ApiIntegration\Plugin\Frontend;

use Loqate\ApiIntegration\Helper\Controller;
use Loqate\ApiIntegration\Logger\Logger;
use Loqate\ApiIntegration\Plugin\AbstractPlugin;
use Magento\Checkout\Model\ShippingInformation;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Magento\Customer\Model\Session;
use Loqate\ApiIntegration\Helper\Validator;
use Loqate\ApiIntegration\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote;

/**
 * Class CheckoutShippingInformation
 */
class CheckoutShippingInformation extends AbstractPlugin
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
     * Validate shipping address information
     *
     * @throws StateException
     */
    public function aroundSaveAddressInformation(
        ShippingInformationManagement $subject,
        callable                      $proceed,
        $cartId,
        ShippingInformation $addressInformation
    ) {
        if (empty($this->helper->getConfigValue('loqate_settings/settings/api_key'))) {
            $proceed($cartId, $addressInformation);
        }

        if ($shippingAddress = $addressInformation->getShippingAddress()->getData()) {
            $errors = [];
            if ($this->helper->getConfigValueForWebsite('loqate_settings/address_settings/enable_checkout')) {
                $response = $this->validator->verifyAddress($shippingAddress);
                if (!empty($response['error'])) {
                    $errors[] = $response['message'];
                }
            }

            if ($this->helper->getConfigValueForWebsite('loqate_settings/phone_settings/enable_checkout')) {
                if (isset($shippingAddress['telephone'])) {
                    $errorMessage = $this->validatePhone($shippingAddress['telephone'], $shippingAddress['country_id']);
                    if ($errorMessage) {
                        $errors[] = $errorMessage;
                    }
                }
            }

            if ($this->helper->getConfigValueForWebsite('loqate_settings/email_settings/enable_checkout')
            && ($email = $this->session->getData('loqate_email_to_validate'))) {
                $errorMessage = $this->validateEmail($email);
                if ($errorMessage) {
                    $errors[] = $errorMessage;
                } else {
                    $this->session->setData('loqate_email_to_validate', '');
                }
            }

            if ($errors) {
                throw new StateException(__(implode(PHP_EOL, $errors)));
            }
        }

        return $proceed($cartId, $addressInformation);
    }

//todo remove this or save custom attributes from request onto quote
//todo remove logger

//    public function afterSaveAddressInformation(
//        ShippingInformationManagement $subject,
//        $result,
//        $cartId,
//        $addressInformation
//    ) {
//        $this->logger->info($addressInformation->getShippingAddress()->getCustomAttribute('loqate_field1_format'));
//        $this->logger->info($subject->saveAddressInformation($cartId)->getExtensionAttributes());
//
//
//        /** @var Quote $quote */
//        $quote = $this->cartRepository->get($cartId);
//
//        $shippingAddress = $quote->getShippingAddress();
//
//        $customerAddressId = $shippingAddress->getCustomerAddressId();
//
//        $customerAddress = $this->addressRepository->getById($customerAddressId);
//
//        for ($i = 1; $i <= Controller::MAX_DATA_SETS_FIELDS; $i++) {
//            $field = "loqate_field{$i}_format";
//            $customAttribute = $customerAddress->getCustomAttribute($field);
//            if (!empty($customAttribute)) {
//                $shippingAddress->setData($field, $customAttribute->getValue());
//            }
//        }
//        $shippingAddress->save();
//
//        return $result;
//    }
}
