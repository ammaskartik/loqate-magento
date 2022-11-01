<?php

namespace Loqate\ApiIntegration\Controller\Adminhtml\Capture;

use Loqate\ApiIntegration\Helper\Controller;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Find class
 */
class Find implements ActionInterface
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
     * Call capture find API endpoint using PHP library
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        return $this->controllerHelper->find();
    }

    protected function _isAllowed()
    {
        return true;
    }
}
