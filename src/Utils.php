<?php
namespace AsyncIO;

class Utils
{
    public static function asyncMap(array $arr, callable $cb, callable $done=null){
        $results = [];
        $count = count($arr);
        $completed = 0;
        foreach($arr as $k=>$v){
            Async::add(function() use ($v,$k,$cb,&$results,&$completed,$count,$done){
                $cb($v,function($res) use ($k,&$results,&$completed,$count,$done){
                    $results[$k]=$res;
                    $completed++;
                    if($completed==$count && $done) $done($results);
                });
            });
        }
        return $done===null ? $results : null;
    }
}
