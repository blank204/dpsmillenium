<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

class AuditEntry extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit entries are append-only.'));
        static::deleting(fn () => throw new LogicException('Audit entries are append-only.'));
    }

    public function beforeData(): ?array
    {
        return $this->before_json === null ? null : json_decode($this->before_json, true);
    }

    public function afterData(): ?array
    {
        return $this->after_json === null ? null : json_decode($this->after_json, true);
    }

    /** The exact string hashed for this row. Order and formats are fixed forever. */
    public function canonicalPayload(): string
    {
        return json_encode([
            'occurred_at' => $this->occurred_at->format('Y-m-d H:i:s'),
            'actor_name' => $this->actor_name,
            'account_id' => $this->account_id === null ? null : (int) $this->account_id,
            'account_role' => $this->account_role,
            'action' => $this->action,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id === null ? null : (int) $this->target_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'session_id' => $this->session_id,
            'before_json' => $this->before_json,
            'after_json' => $this->after_json,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
