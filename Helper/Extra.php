<?php

namespace Loqate\ApiIntegration\Helper;

use Loqate\ApiConnector\Client\Extras;
use Loqate\ApiIntegration\Logger\Logger;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Extra class
 */
class Extra
{

    /** @var Extras $apiExtras */
    private $apiExtras;

    /** @var Logger $logger */
    private $logger;

    private Data $helper;
    private RemoteAddress $remoteAddress;

    /**
     * Find constructor
     *
     * @param Logger $logger
     * @param Data $helper
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        RemoteAddress $remoteAddress
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->remoteAddress = $remoteAddress;

        if ($apiKey = $this->helper->getConfigValue('loqate_settings/settings/api_key')) {
            $this->apiExtras = new Extras($apiKey);
        } else {
            $this->logger->info('No Api Key found! - Please configure Loqate plugin on Admin side!');
        }
    }

    /**
     * Call ip2Country API endpoint use PHP library
     *
     */
    public function ipToCountry()
    {
        if ($this->apiExtras) {
            $addressIp = $this->remoteAddress->getRemoteAddress();
            $apiRequestParams = ['IpAddress' => $addressIp];

            $result = $this->apiExtras->ipToCountry($apiRequestParams);

            if (isset($result['error'])) {
                $this->logger->info($result['message']);

                return null;
            }

            return $result;
        } else {
            return null;
        }
    }
}
