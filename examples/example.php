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

// ================= Promise Example =================
$p1 = new Promise(function($resolve,$reject){
    Timer::setTimeout(1000, fn() => $resolve("Promise 1 resolved"));
});

$p2 = new Promise(function($resolve,$reject){
    Timer::setTimeout(2000, fn() => $resolve("Promise 2 resolved"));
});

Promise::all([$p1,$p2], fn($results) => Logger::log("[Promise all] ".implode(", ", $results)));
Promise::race([$p1,$p2], fn($winner) => Logger::log("[Promise race] $winner"));

// ================= Database Async =================
class MyDB extends DB {
    public function __construct(){
        parent::__construct("mysql:host=127.0.0.1;dbname=demo","root","");
    }
}

$db = new MyDB();
$db->queryAsync("SELECT id,name FROM users LIMIT 3", function($res){
    foreach($res as $row){
        Logger::log("[DB] {$row['id']} - {$row['name']}");
    }
});

// ================= HTTP Task =================
$httpTask = function(){
    Http::requestAsync(
        ['https://jsonplaceholder.typicode.com/todos/2'],
        ['timeout'=>5,'method'=>'GET'],
        fn($res) => Logger::log("[HTTP] Got ".count($res)." responses")
    );
};
Async::add($httpTask);

// ================= Task =================
// Single task
$singleTask = new Task(function(){
    Logger::log("[Task] Start single task...");
    Timer::setTimeout(5000, function(){
        Logger::log("[Task] Finish single task after 5 seconds");
    });
}, "singleTask", 1);
Async::add($singleTask->callable);

// Multi task
$multiTasks = [];
for($i=1; $i<=3; $i++){
    $multiTasks[] = new Task(function() use ($i){
        Logger::log("[Task-$i] Start multi task...");
        $delay = rand(2000,5000);
        Timer::setTimeout($delay, function() use ($i, $delay){
            Logger::log("[Task-$i] Finish multi task after {$delay} ms");
        });
    }, "task-$i", $i);
}
foreach($multiTasks as $t){
    Async::add($t->callable);
}

// ================= Run Worker Queue + Async Engine =================
Worker::runQueue();  // run CPU-bound tasks
Async::run();        // run async engine
