<?php

namespace Loqate\ApiIntegration\Controller\Extras;

use Loqate\ApiIntegration\Helper\Controller;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Ipcountry class
 */
class Ipcountry implements ActionInterface
{
    /** @var Controller $controllerHelper */
    private $controllerHelper;

    /**
     * Find constructor
     *
     * @param Controller $controllerHelper
     */
    public function __construct(Controller $controllerHelper)
    {
        $this->controllerHelper = $controllerHelper;
    }

    /**
     * Call Ipcountry find API endpoint using PHP library
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        return $this->controllerHelper->ipToCountry();
    }
}
