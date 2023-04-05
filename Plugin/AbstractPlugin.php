<?php

namespace Loqate\ApiIntegration\Plugin;

use Loqate\ApiIntegration\Helper\Data;
use Loqate\ApiIntegration\Helper\Validator;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\UrlInterface;

/**
 * AbstractPlugin class
 */
abstract class AbstractPlugin
{
    /** @var MessageManagerInterface */
    protected $messageManager;

    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var RedirectFactory */
    protected $resultRedirectFactory;

    /** @var RedirectInterface */
    protected $redirect;

    /** @var Session */
    protected $session;

    /** @var Validator */
    protected $validator;

    /** @var Data */
    protected $helper;

    /** @var JsonFactory */
    protected $resultJsonFactory;

    /**
     * AbstractPlugin constructor
     *
     * @param Context $context
     * @param UrlInterface $urlBuilder
     * @param Session $session
     * @param Validator $validator
     * @param Data $helper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        UrlInterface $urlBuilder,
        Session $session,
        Validator $validator,
        Data $helper,
        JsonFactory $resultJsonFactory
    ) {
        $this->messageManager = $context->getMessageManager();
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->redirect = $context->getRedirect();
        $this->urlBuilder = $urlBuilder;
        $this->session = $session;
        $this->validator = $validator;
        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     *
     * Check if email/telephone was already checked and store that value in session
     *
     * @param $field
     * @param $value
     * @return bool
     */
    protected function shouldVerify($field, $value)
    {
        $storedData = (
            $this->session->getData($field)
            ? $this->session->getData($field)
            : []
        );
        if ($this->session->getData($field) && in_array($value, $storedData)) {
            return false;
        }

        $storedData[] = $value;

        $this->session->setData($field, $storedData);
        return true;
    }

    /**
     * Validate email address
     *
     * @param $email
     * @return false|Phrase
     */
    protected function validateEmail($email)
    {
        $errorMessage = __('The provided email address is invalid.');
        if (!$this->helper->getConfigValueForWebsite('loqate_settings/email_settings/prevent_submit')) {
            if (!$this->shouldVerify('loqate_email', $email)) {
                return false;
            }
            $errorMessage = __('Invalid email address. Submit again to use this email address.');
        }

        $response = $this->validator->verifyEmail($email);

        if (isset($response['error'])) {
            return __('An unexpected error occurred while trying to validate your email address.');
        }

        if (!$response) {
            return $errorMessage;
        }

        if (isset($response['noKeyFound'])) {
            return false;
        }

        return false;
    }

    /**
     * Validate phone number
     *
     * @param $phone
     * @return false|Phrase
     */
    protected function validatePhone($phone)
    {
        $errorMessage = __('The provided phone number is invalid.');
        if (!$this->helper->getConfigValueForWebsite('loqate_settings/phone_settings/prevent_submit')) {
            if (!$this->shouldVerify('loqate_phone', $phone)) {
                return false;
            }
            $errorMessage = __('Invalid phone number. Submit again to use this phone number.');
        }

        $response = $this->validator->verifyPhoneNumber($phone);

        if (isset($response['error'])) {
            return __('An unexpected error occurred while trying to validate your phone number.');
        }

        if (!$response) {
            return $errorMessage;
        }

        if (isset($response['noKeyFound'])) {
            return false;
        }

        return false;
    }
}
