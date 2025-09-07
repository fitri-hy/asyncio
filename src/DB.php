<?php
namespace AsyncIO;

class DB {
    private \PDO $pdo;

    public function __construct(string $dsn,string $user='',$pass=''){
        $this->pdo=new \PDO($dsn,$user,$pass);
    }

    public function queryAsync(string $sql, callable $callback){
        Async::add(function() use ($sql,$callback){
            try{
                $stmt=$this->pdo->query($sql);
                $res=$stmt?$stmt->fetchAll(\PDO::FETCH_ASSOC):[];
                $callback($res);
            }catch(\Throwable $e){
                Logger::log("[DB Error] ".$e->getMessage(),'ERROR');
                $callback([]);
            }
        });
    }

    public function transactionAsync(callable $callback){
        Async::add(function() use ($callback){
            try{
                $this->pdo->beginTransaction();
                $callback($this->pdo);
                $this->pdo->commit();
            }catch(\Throwable $e){
                $this->pdo->rollBack();
                Logger::log("[DB Error] ".$e->getMessage(),'ERROR');
            }
        });
    }
}
