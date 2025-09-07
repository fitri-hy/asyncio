<?php
namespace AsyncIO;

class Timer
{
    private static array $timeouts = [];
    private static array $intervals = [];
    private static int $idCounter = 0;

    public static function setTimeout(int $ms, callable $callback): int {
        $id = self::$idCounter++;
        self::$timeouts[$id] = [
            'at' => microtime(true) + $ms/1000,
            'cb' => $callback
        ];
        return $id;
    }

    public static function setInterval(int $ms, callable $callback): int {
        $id = self::$idCounter++;
        self::$intervals[$id] = [
            'interval' => $ms/1000,
            'next' => microtime(true) + $ms/1000,
            'cb' => $callback
        ];
        return $id;
    }

    public static function cancel(int $id): void {
        unset(self::$timeouts[$id], self::$intervals[$id]);
    }

    public static function tick(): void {
        $now = microtime(true);

        foreach (self::$timeouts as $id => $t) {
            if ($now >= $t['at']) {
                Async::add($t['cb']);
                unset(self::$timeouts[$id]);
            }
        }

        foreach (self::$intervals as $id => &$t) {
            if ($now >= $t['next']) {
                Async::add($t['cb']);
                $t['next'] = $now + $t['interval'];
            }
        }
    }

    public static function isIdle(): bool {
        return empty(self::$timeouts) && empty(self::$intervals);
    }
}
