<?php
namespace AsyncIO;

class Task {
    public string $name;
    public int $priority;
    public bool $cancelled = false;
    public $callable;

    public function __construct(callable $callable, ?string $name = null, int $priority = 0){
        $this->callable = $callable;
        $this->name = $name ?? 'task_'.spl_object_id($this);
        $this->priority = $priority;
    }

    public function cancel(): void {
        $this->cancelled = true;
    }
}
