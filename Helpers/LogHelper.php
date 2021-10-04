<?php

namespace WjCrypto\Helpers;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

trait LogHelper
{
    use CryptografyHelper;

    /**
     * @param string $message
     * @param string $logFileName
     * @param string $loggerName
     * @param int $loggerLevel
     * @param array $contextArray
     */
    public function registerLog(
        string $message,
        string $logFileName,
        string $loggerName,
        int $loggerLevel,
        array $contextArray = []
    ) {
        $logger = new Logger($loggerName);
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../Logs/' . $logFileName . '.log', $loggerLevel));
        $logger->info($message, $contextArray);
    }

    /**
     * @param string $accountNumber
     * @return array
     */
    public function getAccountTransactionLogs(string $accountNumber): array
    {
        if (file_exists(__DIR__ . '/../Logs/transaction.log')) {
            $encryptedAccountNumber = $this->encrypt($accountNumber);
            $transactions = file_get_contents(__DIR__ . '/../Logs/transaction.log');
            $matches = [];
            $returnArray = [];
            preg_match_all('/({.+})+/', $transactions, $matches);
            foreach ($matches[0] as $match) {
                if (str_contains($match, $encryptedAccountNumber)) {
                    $returnArray[] = json_decode($match);
                }
            }
            return $returnArray;
        }
        return [];
    }
}
