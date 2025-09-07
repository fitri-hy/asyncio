<?php
namespace AsyncIO;

class Logger {
    public static function log($msg,$level='INFO'){
        echo "[".date('H:i:s')."][".$level."] ".$msg.PHP_EOL;
    }
}
