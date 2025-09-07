<?php
namespace AsyncIO;

class Http {
    public static function requestAsync(array $urls, array $options, callable $callback){
        Async::add(function() use ($urls,$options,$callback){
            $results=[];
            foreach($urls as $url){
                $ch=curl_init();
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                if(!empty($options['timeout'])) curl_setopt($ch,CURLOPT_TIMEOUT,$options['timeout']);
                if(!empty($options['headers'])) curl_setopt($ch,CURLOPT_HTTPHEADER,$options['headers']);
                if(!empty($options['method'])){
                    curl_setopt($ch,CURLOPT_CUSTOMREQUEST,$options['method']);
                    if(!empty($options['body'])) curl_setopt($ch,CURLOPT_POSTFIELDS,$options['body']);
                }
                $res=curl_exec($ch);
                $err=curl_error($ch);
                curl_close($ch);
                $results[$url]=$err?null:$res;
            }
            $callback($results);
        });
    }
}
