<?php
namespace AsyncIO;

class Http {
    public static function requestAsync(array $urls, array $options, callable $callback){
        Async::add(function() use ($urls,$options,$callback){
            $multiHandle = curl_multi_init();
            $handles = [];
            $results = [];

            foreach($urls as $url){
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

                if(!empty($options['timeout'])) curl_setopt($ch,CURLOPT_TIMEOUT,$options['timeout']);
                if(!empty($options['headers'])) curl_setopt($ch,CURLOPT_HTTPHEADER,$options['headers']);

                $method = strtoupper($options['method'] ?? 'GET');
                curl_setopt($ch,CURLOPT_CUSTOMREQUEST,$method);

                if(in_array($method,['POST','PUT','PATCH']) && !empty($options['body'])){
                    curl_setopt($ch,CURLOPT_POSTFIELDS,$options['body']);
                }

                curl_multi_add_handle($multiHandle, $ch);
                $handles[$url] = $ch;
            }

            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while($running > 0);

            foreach($handles as $url => $ch){
                $err = curl_error($ch);
                $res = curl_multi_getcontent($ch);
                $results[$url] = $err ? null : $res;
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }
            curl_multi_close($multiHandle);

            $callback($results);
        });
    }
	
	public static function requestPromise(array $urls, array $options): \AsyncIO\Promise {
        return new \AsyncIO\Promise(function($resolve, $reject) use ($urls, $options){
            self::requestAsync($urls, $options, function($res) use ($resolve){
                $resolve($res);
            });
        });
    }
}
