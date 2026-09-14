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

    public function record(string $action, Actor $actor, ?Model $target = null, ?array $before = null, ?array $after = null): AuditEntry
    {
        return DB::transaction(function () use ($action, $actor, $target, $before, $after) {
            $last = AuditEntry::query()->orderByDesc('id')->lockForUpdate()->first();

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
            $entry->prev_hash = $last?->hash ?? self::GENESIS;
            $entry->hash = hash('sha256', $entry->prev_hash.$entry->canonicalPayload());
            $entry->save();

            return $entry;
        });
    }
}
