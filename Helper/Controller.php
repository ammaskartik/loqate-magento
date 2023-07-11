<?php

namespace Loqate\ApiIntegration\Helper;

use Loqate\ApiConnector\Client\Capture;
use Loqate\ApiIntegration\Logger\Logger;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Controller class
 */
class Controller
{
    const MAX_DATA_SETS_FIELDS = 20;

    /** @var Capture $apiConnector */
    private $apiConnector;

    /** @var ResultFactory $resultJsonFactory */
    protected $resultJsonFactory;

    /** @var RequestInterface $request */
    protected $request;

    /** @var Logger $logger */
    private $logger;

    /** @var Session $session */
    private $session;

    /** @var string */
    private $version = null;

    private Data $helper;

    /**
     * Find constructor
     *
     * @param ResultFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param Logger $logger
     * @param Session $session
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        ResultFactory $resultJsonFactory,
        RequestInterface $request,
        Logger $logger,
        Session $session,
        ModuleListInterface $moduleList,
        Data $helper
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->session = $session;
        $this->helper = $helper;

        if ($apiKey = $this->helper->getConfigValue('loqate_settings/settings/api_key')) {
            $this->apiConnector = new Capture($apiKey);
        } else {
            $this->logger->info('No Api Key found! - Please configure Loqate plugin on Admin side!');
            return false;
        }
        $this->version = 'AdobeCommerce_v' . $moduleList->getOne('Loqate_ApiIntegration')['setup_version'];
    }

    /**
     * Call capture find API endpoint using PHP library
     *
     * @return ResponseInterface|ResultInterface
     */
    public function find()
    {
        $resultJson = $this->resultJsonFactory->create(ResultFactory::TYPE_JSON);
        if ($this->apiConnector) {
            $searchText = $this->request->getParam('text');
            $origin = $this->request->getParam('origin');

            $apiRequestParams = ['Text' => $searchText, 'source' => $this->version];
            if (!empty($origin)) {
                $apiRequestParams['Origin'] = $origin;
            }

            $countries = $this->helper->getConfigValue('loqate_settings/capture_settings/restrict_countries');
            if (!empty($countries)) {
                $apiRequestParams['Countries'] = $countries;
            }

            $result = $this->apiConnector->find($apiRequestParams);

            if (isset($result['error'])) {
                $this->logger->info($result['message']);
                return $resultJson->setData(
                    ['error' => true, 'message' => __('Error occurred while trying to process your request')]
                );
            }

            return $resultJson->setData($result);
        } else {
            return $resultJson->setData(['error' => true, 'message' => __('Object could not be initialized')]);
        }
    }

    /**
     * Call capture retrieve API endpoint use PHP library
     *
     * @return ResponseInterface|ResultInterface
     */
    public function retrieve()
    {
        $resultJson = $this->resultJsonFactory->create(ResultFactory::TYPE_JSON);
        if ($this->apiConnector) {
            $addressId = $this->request->getParam('address_id');
            $apiRequestParams = ['Id' => $addressId, 'source' => $this->version];

            $premiumDataSetsFields = $this->getPremiumDataSetsFields();

            if (!empty($premiumDataSetsFields)) {
                $apiRequestParams = array_merge($apiRequestParams, $premiumDataSetsFields);
            }

            $result = $this->apiConnector->retrieve($apiRequestParams);

            if (isset($result['error'])) {
                $this->logger->info($result['message']);
                return $resultJson->setData(
                    ['error' => true, 'message' => __('Error occurred while trying to process your request')]
                );
            }

            if (is_array($result)) {
                $this->storeCapturedAddress($result[0]);
            }

            return $resultJson->setData($result);
        } else {
            return $resultJson->setData(['error' => true, 'message' => __('Object could not be initialized')]);
        }
    }

    /**
     * Store captured address in session so verify is not performed if the address hasn't changed
     *
     * @param $result
     */
    protected function storeCapturedAddress($result)
    {
        $storeArray = [];
        foreach (Validator::ADDRESS_CAPTURE_MAPPING as $key => $value) {
            $storeArray[$key] = $result[$value];
        }

        $capturedAddresses = (
            $this->session->getData('captured_addresses')
            ? $this->session->getData('captured_addresses')
            : []
        );

        $capturedAddresses[] = serialize($storeArray);
        $this->session->setData('captured_addresses', $capturedAddresses);
    }

    protected function getPremiumDataSetsFields()
    {
        $data = [];

        for ($i = 1; $i <= self::MAX_DATA_SETS_FIELDS; $i++) {
            $fieldValue = $this->helper->getConfigValue("loqate_settings/premium_data_sets/field{$i}_format",);
            if (!empty($fieldValue)) {
                $data["Field{$i}Format"] = "{{$fieldValue}}";
            }
        }

        return $data;
    }
}
