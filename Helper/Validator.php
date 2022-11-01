<?php

namespace Loqate\ApiIntegration\Helper;

use Loqate\ApiConnector\Client\Verify;
use Loqate\ApiIntegration\Logger\Logger;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Validator
 */
class Validator
{
    const ADDRESS_MAPPING = [
        'street' => 'Address',
        'street_1' => 'Address1',
        'street_2' => 'Address2',
        'city' => 'Address3',
        'region' => 'Address4',
        'postcode' => 'PostalCode',
        'country_id' => 'Country'
    ];

    const ADDRESS_CAPTURE_MAPPING = [
        'Address1' => 'Line1',
        'Address2' => 'Line2',
        'Country' => 'CountryIso2',
        'PostalCode' => 'PostalCode',
        'Address3' => 'City',
        'Address4' => 'ProvinceName'
    ];

    /** @var Verify $apiConnector */
    private $apiConnector;

    /** @var ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var Logger $logger */
    private $logger;

    /** @var Session $session */
    private $session;

    /** @var RegionFactory */
    private $regionFactory;

    /**
     * Validator construct
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param Session $session
     * @param RegionFactory $regionFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger               $logger,
        Session              $session,
        RegionFactory        $regionFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->session = $session;
        $this->regionFactory = $regionFactory;
        if ($apiKey = $this->scopeConfig->getValue('loqate_settings/settings/api_key')) {
            $this->apiConnector = new Verify($apiKey);
        }
    }

    /**
     * Verify email address
     *
     * @param $emailAddress
     * @return array
     */
    public function verifyEmail($emailAddress)
    {
        $response = $this->apiConnector->verifyEmail(['Email' => $emailAddress]);

        if (isset($response['error'])) {
            $this->logger->info($response['message']);
        }

        return $response;
    }

    /**
     * Verify phone number
     *
     * @param $phoneNumber
     * @return array
     */
    public function verifyPhoneNumber($phoneNumber)
    {
        $response = $this->apiConnector->verifyPhone(['Phone' => $phoneNumber]);

        if (isset($response['error'])) {
            $this->logger->info($response['message']);
        }

        return $response;
    }

    /**
     * Verify single address using Loqate API
     *
     * @param $address
     * @param $checkForCaptured
     * @return array
     */
    public function verifyAddress($address, $checkForCaptured = true): array
    {
        $requestArray = $this->parseAddress($address);
        if ($checkForCaptured && ($storedAddresses = $this->session->getData('captured_addresses'))) {
            if ($this->checkForCapturedAddress($requestArray, $storedAddresses)) {
                return ['error' => false];
            }
        }

        $response = $this->apiConnector->verifyAddress(['Addresses' => [$requestArray]]);

        if (isset($response['error'])) {
            $this->logger->info($response['message']);
            return ['error' => true, 'message' => __('An unexpected error occurred while trying to validate your address.')];
        }

        if (!$this->checkQualityIndex($response[0][0]['AQI'])) {
            return ['error' => true, 'message' => __('The provided address is invalid.')];
        }

        return ['error' => false];
    }

    /**
     * Verify multiple addresses using Loqate API
     *
     * @param $addresses
     * @param bool $checkForCaptured
     * @return array|false
     */
    public function verifyMultipleAddresses($addresses, $checkForCaptured = true)
    {

        if ($checkForCaptured) {
            $storedAddresses = $this->session->getData('captured_addresses');
        }

        $requestArray = [];
        foreach ($addresses as $index => $address) {
            $parsedAddress = $this->parseAddress($address);
            if (isset($storedAddresses)
                && $storedAddresses
                && ($checkedAddress = $this->checkForCapturedAddress($address, $storedAddresses))) {
                //store all the address keys in a new array, so we can preserve the original keys/identifiers
                //because we are not sending the original array for validation and we need them to display results
                $addressesToCheck[$index] = $checkedAddress;
                continue;
            }
            $requestArray[] = $parsedAddress;
        }


        if (!$requestArray && isset($addressesToCheck)) {
            return $addressesToCheck;
        }

        $response = $this->apiConnector->verifyAddress(['Addresses' => $requestArray]);
        if (isset($response['error'])) {
            $this->logger->info($response['message']);
            return false;
        }

        $result = [];
        if (isset($addressesToCheck)) {
            foreach ($response as $address) {
                $originalPos = array_search(false, $addressesToCheck);
                $result[$originalPos] = $this->checkQualityIndex($address[0]['AQI']);
            }
        } else {
            foreach ($response as $address) {
                $result[] = $this->checkQualityIndex($address[0]['AQI']);
            }
        }

        return $result;
    }

    /**
     * Parse address and return expected format for verify request
     *
     * @param $address
     * @return array
     */
    private function parseAddress($address): array
    {
        $formattedAddress = ['Address' => ''];

        //get region name
        if (isset($address['region_id']) && $address['region_id']) {
            $region = $this->regionFactory->create()->load($address['region_id']);
            $address['region'] = $region->getName();
        }

        foreach (self::ADDRESS_MAPPING as $key => $value) {
            if (isset($address[$key]) && !is_array($address[$key])) {
                $formattedAddress[$value] = $address[$key];
            }
        }

        return $formattedAddress;
    }

    /**
     * Check if response quality index matches the quality customer has set
     *
     * @param $qualityIndex
     * @return bool
     */
    private function checkQualityIndex($qualityIndex): bool
    {
        $configIndex = $this->scopeConfig->getValue('loqate_settings/address_settings/address_quality_index');

        return $qualityIndex <= $configIndex;
    }

    /**
     * Check for captured addresses, so they should not be verified if already captured
     * @param $address
     * @param $storedAddresses
     * @return bool
     */
    private function checkForCapturedAddress($address, $storedAddresses): bool
    {
        $formattedAddress = [];
        foreach (self::ADDRESS_CAPTURE_MAPPING as $key => $value) {
            if (isset($address[$key]) && !is_array($address[$key])) {
                $formattedAddress[$key] = $address[$key];
            }
        }

        if (in_array(serialize($formattedAddress), $storedAddresses)) {
            return true;
        }

        return false;
    }
}
