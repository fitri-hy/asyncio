<?php
namespace AsyncIO;

class Async {
    private static array $tasks = [];
    private static array $hooksBefore = [];
    private static array $hooksAfter = [];
    private static $onError = null;

    public static function add(callable|\Generator $task, int $priority = 0, ?string $name = null): Task {
        $t = new Task($task, $name, $priority);
        self::$tasks[] = $t;
        usort(self::$tasks, fn($a,$b)=>$b->priority <=> $a->priority);
        return $t;
    }

    public static function addHook(string $type, callable $hook, $filter = null){
        if($type==='before') self::$hooksBefore[]=['hook'=>$hook,'filter'=>$filter];
        if($type==='after') self::$hooksAfter[]=['hook'=>$hook,'filter'=>$filter];
    }

    public static function onError(callable $cb){ self::$onError=$cb; }

    public static function co(callable $genFunc, int $priority=0, ?string $name=null): Task {
        $wrapper = function() use ($genFunc){
            $gen = $genFunc();
            if(!$gen instanceof \Generator) return;
            try{
                while($gen->valid()){
                    $yielded = $gen->current();
                    if($yielded instanceof Promise){
                        $yielded->then(fn($v)=>$gen->send($v));
                        return;
                    }
                    if(is_callable($yielded)){
                        $yielded(fn($res)=>$gen->send($res));
                        return;
                    }
                    $gen->send($yielded);
                }
            }catch(\Throwable $e){
                if(self::$onError) (self::$onError)($e);
            }
        };
        return self::add($wrapper,$priority,$name);
    }

    public static function run(){
        while(!empty(self::$tasks)){
            $task=array_shift(self::$tasks);
            if($task->cancelled) continue;

            foreach(self::$hooksBefore as $h){
                if(self::filterMatch($h['filter'],$task)){
                    $h['hook']($task);
                }
            }

            try{
                $call=$task->callable;
                $result = is_callable($call)? $call() : $call;
                if($result instanceof \Generator){
                    foreach($result as $yielded){
                        if(is_callable($yielded)) $yielded(fn($res)=>$result->send($res));
                    }
                }
            }catch(\Throwable $e){
                if(self::$onError) (self::$onError)($e);
                else Logger::log("[Async Error] ".$e->getMessage(),'ERROR');
            }

            foreach(self::$hooksAfter as $h){
                if(self::filterMatch($h['filter'],$task)){
                    $h['hook']($task);
                }
            }
        }
    }

    private static function filterMatch($filter, Task $task): bool {
        if($filter===null) return true;
        if(is_string($filter)) return $filter===$task->name;
        if(is_callable($filter)) return (bool)$filter($task);
        return false;
    }
}
