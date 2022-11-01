<?php

namespace Loqate\ApiIntegration\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Logger handler class
 */
class Handler extends Base
{
    protected $loggerType = Logger::INFO;

    protected $fileName = '/var/log/loqate_log_file.log';
}
