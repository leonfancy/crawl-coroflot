<?php

namespace Slim;

class Log
{
    const LOG_FILE = "storage/log.txt";

    public static function info($message)
    {
        static::logMessage("INFO", $message);
    }

    public static function error($message)
    {
        static::logMessage("ERROR", $message);
    }

    public static function debug($message)
    {
        static::logMessage("DEBUG", $message);
    }

    private static function logMessage($level, $message)
    {
        $date = date("y-m-d H:i:s");
        $wrappedMessage = "[$level] [$date] $message \n";
        echo $wrappedMessage;
        file_put_contents(static::LOG_FILE, $wrappedMessage, FILE_APPEND);
    }
}