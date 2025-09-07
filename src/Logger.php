<?php
namespace AsyncIO;

class Logger {

    public static function log($msg, $level = 'INFO') {
        $time = date('H:i:s');

        if (class_exists('\Log')) {
            switch(strtoupper($level)) {
                case 'ERROR':
                    \Log::error("[$time][$level] $msg");
                    break;
                case 'DEBUG':
                    \Log::debug("[$time][$level] $msg");
                    break;
                default:
                    \Log::info("[$time][$level] $msg");
            }
        } else {
            echo "[$time][$level] $msg" . PHP_EOL;
        }
    }
}
