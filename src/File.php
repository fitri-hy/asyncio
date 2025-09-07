<?php
namespace AsyncIO;

class File {

    public static function readAsync(string $path, callable $callback){
        Async::add(function() use ($path, $callback){
            if(!file_exists($path)){
                Logger::log("[File Error] File $path not found",'ERROR');
                $callback(null);
                return;
            }
            $content = file_get_contents($path);
            $callback($content);
        });
    }

    public static function readStreamAsync(string $path, callable $chunkCallback, int $chunkSize=8192){
        Async::add(function() use ($path,$chunkCallback,$chunkSize){
            if(!file_exists($path)){
                Logger::log("[File Error] File $path not found",'ERROR');
                return;
            }
            $handle=fopen($path,'r');
            if(!$handle) return;

            while(!feof($handle)){
                $chunk=fread($handle,$chunkSize);
                $chunkCallback($chunk);
                usleep(1000);
            }
            fclose($handle);
        });
    }

    public static function writeStreamAsync(string $path, string $data, callable $callback=null){
        Async::add(function() use ($path,$data,$callback){
            try {
                $res = file_put_contents($path, $data);
                if($callback) $callback($res !== false);
            } catch (\Exception $e) {
                Logger::log("[File Error] ".$e->getMessage(),'ERROR');
                if($callback) $callback(false);
            }
        });
    }

    public static function appendAsync(string $path, string $data, callable $callback=null){
        Async::add(function() use ($path, $data, $callback){
            try {
                $res = file_put_contents($path, $data, FILE_APPEND);
                if($callback) $callback($res !== false);
            } catch (\Exception $e) {
                Logger::log("[File Error] ".$e->getMessage(),'ERROR');
                if($callback) $callback(false);
            }
        });
    }

    public static function watchFile(string $path, callable $callback, int $intervalMs=1000){
        $lastMod=0;
        Timer::setInterval($intervalMs, function() use ($path, $callback, &$lastMod){
            if(!file_exists($path)) return;
            $mod=filemtime($path);
            if($mod!=$lastMod){
                $lastMod=$mod;
                $callback($path);
            }
        });
    }
}
