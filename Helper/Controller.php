<?php

namespace Loqate\ApiIntegration\Helper;

use Loqate\ApiConnector\Client\Capture;
use Loqate\ApiIntegration\Logger\Logger;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Controller class
 */
class Controller
{
    /** @var Capture $apiConnector */
    private $apiConnector;

    /** @var ScopeConfigInterface $scopeConfig */
    private $scopeConfig;

    /** @var ResultFactory $resultJsonFactory */
    protected $resultJsonFactory;

    /** @var RequestInterface $request */
    protected $request;

    /** @var Logger $logger */
    private $logger;

    /** @var Session $session */
    private $session;

    /**
     * Find constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ResultFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param Logger $logger
     * @param Session $session
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ResultFactory        $resultJsonFactory,
        RequestInterface     $request,
        Logger               $logger,
        Session              $session
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->logger = $logger;
        $this->session = $session;
        if ($apiKey = $this->scopeConfig->getValue('loqate_settings/settings/api_key')) {
            $this->apiConnector = new Capture($apiKey);
        }
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
            $apiRequestParams = ['Text' => $searchText];

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
            $apiRequestParams = ['Id' => $addressId];

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
}
