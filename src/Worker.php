<?php
namespace AsyncIO;

class Worker {
    private static array $queue = [];
    private static int $concurrency = 4;
    private static int $running = 0;

    public static function add(callable $task){
        self::$queue[]=$task;
    }

    public static function schedule(callable $task,int $delayMs){
        Timer::setTimeout($delayMs,function() use ($task){
            self::add($task);
        });
    }

    public static function runQueue(){
        while(!empty(self::$queue) || self::$running>0){
            while(self::$running<self::$concurrency && !empty(self::$queue)){
                $task=array_shift(self::$queue);
                self::$running++;
                Async::add(function() use ($task){
                    try{ $task(); } finally{ self::$running--; }
                });
            }
            usleep(1000);
            Async::run();
        }
    }

    public static function setConcurrency(int $n){ self::$concurrency=$n; }
}
