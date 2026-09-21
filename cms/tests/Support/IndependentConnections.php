<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * Two extra DB connections that are NOT wrapped in RefreshDatabase's transaction,
 * so they behave like two concurrent PHP processes (cron + visitor, two editors).
 * Anything they commit must be cleaned up by the test.
 */
trait IndependentConnections
{
    /** @var list<string> */
    private array $independentConnections = [];

    private function independentConnection(string $name, int $lockWaitSeconds = 1): \Illuminate\Database\Connection
    {
        $default = config('database.default');
        config(["database.connections.{$name}" => config("database.connections.{$default}")]);
        DB::purge($name);
        $connection = DB::connection($name);
        $connection->statement('SET SESSION innodb_lock_wait_timeout = '.$lockWaitSeconds);
        $this->independentConnections[] = $name;

        return $connection;
    }

    /** Run $callback with $name as the default connection (models and DB facade use it). */
    private function onConnection(string $name, callable $callback): mixed
    {
        $previous = DB::getDefaultConnection();
        DB::setDefaultConnection($name);
        try {
            return $callback();
        } finally {
            DB::setDefaultConnection($previous);
        }
    }

    private function closeIndependentConnections(): void
    {
        foreach ($this->independentConnections as $name) {
            $connection = DB::connection($name);
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            DB::purge($name);
        }
        $this->independentConnections = [];
    }
}
