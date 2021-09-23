<?php

namespace WjCrypto\Helpers;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

trait LogHelper
{
    /**
     * @param string $message
     * @param string $logFileName
     * @param string $loggerName
     * @param int $loggerLevel
     */
    public function registerLog(string $message, string $logFileName, string $loggerName, int $loggerLevel)
    {
        $logger = new Logger($loggerName);
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../Logs/' . $logFileName . '.log', $loggerLevel));
        $logger->info($message);
    }
}