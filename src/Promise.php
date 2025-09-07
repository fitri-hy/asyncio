<?php
namespace AsyncIO;

class Promise {
    private array $then=[];
    private array $catch=[];
    private string $state='pending';
    private $value;

    public function __construct(callable $executor){
        try{
            $executor([$this,'resolve'],[$this,'reject']);
        }catch(\Throwable $e){
            $this->reject($e);
        }
    }

    public function then(callable $cb){
        if($this->state==='fulfilled') $cb($this->value);
        else $this->then[]=$cb;
        return $this;
    }

    public function catch(callable $cb){
        if($this->state==='rejected') $cb($this->value);
        else $this->catch[]=$cb;
        return $this;
    }

    public function resolve($v){
        if($this->state!=='pending') return;
        $this->state='fulfilled'; $this->value=$v;
        foreach($this->then as $cb) $cb($v);
    }

    public function reject($e){
        if($this->state!=='pending') return;
        $this->state='rejected'; $this->value=$e;
        foreach($this->catch as $cb) $cb($e);
    }

    public static function all(array $promises, callable $callback){
        $results=[]; $count=count($promises); $done=0;
        foreach($promises as $i=>$p){
            $p->then(function($v) use (&$results,$callback,&$done,$count,$i){
                $results[$i]=$v; $done++; if($done==$count) $callback($results);
            });
        }
    }

    public static function race(array $promises, callable $callback){
        $called=false;
        foreach($promises as $p){
            $p->then(function($v) use (&$called,$callback){
                if(!$called){ $called=true; $callback($v); }
            });
        }
    }

    public static function any(array $promises, callable $callback){
        foreach($promises as $p){
            $p->then(function($v) use ($callback){ $callback($v); });
        }
    }

    public static function resolveNow($v){ return new self(fn($res)=>$res($v)); }
}
