<?php
namespace AsyncIO;

class Timer
{
    private static array $timers = [];
    private static int $idCounter = 0;

    public static function setTimeout(int $ms, callable $callback){
        $id = self::$idCounter++;
        self::$timers[$id] = ['type'=>'timeout','time'=>microtime(true)+$ms/1000,'callback'=>$callback];
        Async::add(fn()=> self::check($id));
        return $id;
    }

    public static function setInterval(int $ms, callable $callback){
        $id = self::$idCounter++;
        self::$timers[$id] = ['type'=>'interval','time'=>microtime(true)+$ms/1000,'ms'=>$ms,'callback'=>$callback];
        Async::add(fn()=> self::check($id));
        return $id;
    }

    public static function cancel(int $id){ unset(self::$timers[$id]); }

    private static function check(int $id){
        if(!isset(self::$timers[$id])) return;
        $t = self::$timers[$id];
        $now = microtime(true);
        if($now >= $t['time']){
            $t['callback']();
            if($t['type']=='interval'){
                self::$timers[$id]['time']=$now+$t['ms']/1000;
                Async::add(fn()=> self::check($id));
            }else unset(self::$timers[$id]);
        }else Async::add(fn()=> self::check($id));
    }
}
