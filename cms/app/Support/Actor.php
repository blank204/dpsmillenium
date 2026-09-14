<?php

namespace App\Support;

use Illuminate\Http\Request;

final class Actor
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?int $accountId,
        public readonly ?string $role,
        public readonly ?string $ip,
        public readonly ?string $userAgent,
        public readonly ?string $sessionId,
    ) {}

    public static function fromRequest(Request $request, ?string $name): self
    {
        $account = $request->user();

        return new self(
            $name === null ? null : trim($name),
            $account?->id,
            $account?->role,
            $request->ip(),
            mb_substr((string) $request->userAgent(), 0, 500),
            $request->hasSession() ? $request->session()->getId() : null,
        );
    }

    public static function system(): self
    {
        return new self('System (scheduled)', null, 'system', null, null, null);
    }
}
