<?php
require __DIR__.'/../vendor/autoload.php';

use AsyncIO\Async;
use AsyncIO\File;
use AsyncIO\Http;
use AsyncIO\Timer;
use AsyncIO\Worker;
use AsyncIO\Task;
use AsyncIO\DB;
use AsyncIO\Utils;
use AsyncIO\Promise;
use AsyncIO\Logger;

// ================= Logger =================
Logger::log("[Logger] Starting AsyncIO v10");

// ================= File Async =================
File::readAsync(__DIR__.'/data/sample.txt', function($content){
    Logger::log("[File] Reading async: $content");
});

File::readStreamAsync(__DIR__.'/data/sample.txt', function($chunk){
    Logger::log("[File Stream] Chunk size: ".strlen($chunk));
});

File::watchFile(__DIR__.'/data/sample.txt', function($path){
    Logger::log("[File Watcher] File changed: $path");
});

// ================= Worker Pool =================
Worker::setConcurrency(3);
for ($i=0; $i<3; $i++){
    $idx = $i;
    Worker::add(function() use ($idx){
        sleep(1); // simulate CPU-heavy task
        Logger::log("[Worker-$idx] CPU task completed");
    });
}

// ================= Worker Scheduler =================
Worker::schedule(function(){
    Logger::log("[Scheduler] Running scheduled tasks");

    Utils::asyncMap([1,2,3,4,5], function($n,$done){
        $done($n*$n);
    }, function($res){
        Logger::log("[Utils] asyncMap result: ".implode(",", $res));
    });

    Http::requestAsync(
        ['https://jsonplaceholder.typicode.com/todos/1'],
        ['timeout'=>5,'method'=>'GET'],
        function($res){
            Logger::log("[HTTP] Got ".count($res)." responses");
        }
    );

}, 5000);

// ================= Timer / Interval =================
Timer::setTimeout(1500, function(){
    Logger::log("[Timer] 1.5 seconds passed");
});

Timer::setInterval(5000, function(){
    Logger::log("[Interval] Every 5 seconds appears");
});

// ================= Utils Example =================
Utils::asyncMap([10,20,30], function($n,$done){
    $done($n*2);
}, function($res){
    Logger::log("[Utils] Doubled values: ".implode(",", $res));
});

// ================= Promise CRUD (JSONPlaceholder) =================

Async::add(function(){

    // CREATE (POST)
    Http::requestPromise(
        ['https://jsonplaceholder.typicode.com/posts'],
        [
            'method'=>'POST',
            'headers'=>['Content-Type: application/json'],
            'body'=>json_encode(['title'=>'foo','body'=>'bar','userId'=>1])
        ]
    )
    ->then(function($res){
        Logger::log("[POST] ".substr(current($res),0,50));
        // READ after POST
        return Http::requestPromise(['https://jsonplaceholder.typicode.com/posts/1'], ['method'=>'GET']);
    })
    ->then(function($res){
        Logger::log("[GET] ".substr(current($res),0,50));
        // UPDATE
        return Http::requestPromise(
            ['https://jsonplaceholder.typicode.com/posts/1'],
            [
                'method'=>'PUT',
                'headers'=>['Content-Type: application/json'],
                'body'=>json_encode(['id'=>1,'title'=>'updated','body'=>'updated','userId'=>1])
            ]
        );
    })
    ->then(function($res){
        Logger::log("[PUT] ".substr(current($res),0,50));
        // DELETE
        return Http::requestPromise(['https://jsonplaceholder.typicode.com/posts/1'], ['method'=>'DELETE']);
    })
    ->then(function($res){
        Logger::log("[DELETE] Done");
    })
    ->catch(function($e){
        Logger::log("[HTTP Error] ".$e->getMessage());
    });

});

// ================= Database Async =================
class MyDB extends DB {
    public function __construct(){
        parent::__construct("mysql:host=127.0.0.1;dbname=demo","root","");
    }
}

$db = new MyDB();

// Query async
$db->queryAsync("SELECT id, name FROM users LIMIT 3", function($res){
    foreach($res as $row){
        Logger::log("[DB] {$row['id']} - {$row['name']}");
    }
});

// ================= Single Task =================
$singleTask = new Task(function(){
    Logger::log("[Task] Start single task...");
    Timer::setTimeout(5000, function(){
        Logger::log("[Task] Finish single task after 5 seconds");
    });
}, "singleTask", 1);

Async::add($singleTask->callable);

// ================= Multi Task =================
for($i=1; $i<=3; $i++){
    $t = new Task(function() use ($i){
        Logger::log("[Task-$i] Start multi task...");
        $delay = rand(2000,5000);
        Timer::setTimeout($delay, function() use ($i, $delay){
            Logger::log("[Task-$i] Finish multi task after {$delay} ms");
        });
    }, "task-$i", $i);

    Async::add($t->callable);
}

// ================= Run Worker Queue + Async Engine =================
Worker::runQueue();  // run CPU-bound tasks
Async::run();        // run async engine, otomatis tick Timer/Worker/HTTP/Promise
