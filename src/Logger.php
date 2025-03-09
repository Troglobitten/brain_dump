<?php

namespace BrainDump;

class Logger
{
    protected static $logFile = 'error_log.txt';

    public static function log($message)
    {
        $timestamp = date('[Y-m-d H:i:s]');
        $logMessage = "$timestamp ERROR: $message\n";

        // Write to log file
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);

        // Also output to the console
        echo "\033[31m$logMessage\033[0m"; // Red color in terminal
    }
}
