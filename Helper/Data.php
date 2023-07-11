<?php

namespace Loqate\ApiIntegration\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 */
class Data extends AbstractHelper
{
    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * Data constructor
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Context $context, StoreManagerInterface $storeManager)
    {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * Get current website ID
     *
     * @return int
     */
    public function getCurrentWebsite()
    {
        try {
            return $this->storeManager->getStore()->getWebsiteId();
        } catch (NoSuchEntityException $e) {
            return 0;
        }
    }

    /**
     * Get config value for website
     *
     * @param $configPath
     * @return mixed
     */
    public function getConfigValueForWebsite($configPath)
    {
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_WEBSITE, $this->getCurrentWebsite());
    }

    /**
     * Get config value
     *
     * @param $configPath
     * @return mixed
     */
    public function getConfigValue($configPath)
    {
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE);
    }
}
