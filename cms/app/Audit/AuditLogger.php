<?php

namespace App\Audit;

use App\Models\AuditEntry;
use App\Support\Actor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AuditLogger
{
    public const GENESIS = '0000000000000000000000000000000000000000000000000000000000000000';

    private const HEAD_TABLE = 'audit_chain_head';

    public function record(string $action, Actor $actor, ?Model $target = null, ?array $before = null, ?array $after = null): AuditEntry
    {
        return DB::transaction(function () use ($action, $actor, $target, $before, $after) {
            $head = $this->lockHead();

            $entry = new AuditEntry([
                'occurred_at' => CarbonImmutable::now()->setMicrosecond(0),
                'actor_name' => $actor->name,
                'account_id' => $actor->accountId,
                'account_role' => $actor->role,
                'action' => $action,
                'target_type' => $target ? class_basename($target) : null,
                'target_id' => $target?->getKey(),
                'ip_address' => $actor->ip,
                'user_agent' => $actor->userAgent,
                'session_id' => $actor->sessionId,
                'before_json' => $before === null ? null : json_encode($before, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'after_json' => $after === null ? null : json_encode($after, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ]);
            $entry->prev_hash = $head->hash;
            $entry->hash = hash('sha256', $entry->prev_hash.$entry->canonicalPayload());
            $entry->save();

            DB::table(self::HEAD_TABLE)->where('id', 1)->update(['last_entry_id' => $entry->id, 'hash' => $entry->hash]);

            return $entry;
        });
    }

    /**
     * Lock the single chain-head row for the rest of the current transaction and
     * return it. A primary-key row lock (not a gap/next-key lock on the log's
     * tail) means a concurrent writer always waits, even on an empty log.
     * Must be called inside a transaction.
     */
    public function lockHead(): object
    {
        $head = DB::table(self::HEAD_TABLE)->where('id', 1)->lockForUpdate()->first();

        if ($head === null) {
            // Row missing (e.g. table emptied by hand): recreate it from the log's tail.
            $last = AuditEntry::query()->orderByDesc('id')->first();
            DB::table(self::HEAD_TABLE)->insertOrIgnore(['id' => 1, 'last_entry_id' => $last?->id, 'hash' => $last?->hash ?? self::GENESIS]);
            $head = DB::table(self::HEAD_TABLE)->where('id', 1)->lockForUpdate()->first();
        }

        return $head;
    }
}
