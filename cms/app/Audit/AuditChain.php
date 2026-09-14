<?php

namespace App\Audit;

use App\Models\AuditEntry;

class AuditChain
{
    public function firstBrokenId(): ?int
    {
        $expectedPrev = AuditLogger::GENESIS;
        $broken = null;

        AuditEntry::query()->orderBy('id')->chunkById(500, function ($entries) use (&$expectedPrev, &$broken) {
            foreach ($entries as $entry) {
                $recomputed = hash('sha256', $entry->prev_hash.$entry->canonicalPayload());
                if ($entry->prev_hash !== $expectedPrev || $recomputed !== $entry->hash) {
                    $broken = (int) $entry->id;
                    return false;
                }
                $expectedPrev = $entry->hash;
            }
        });

        return $broken;
    }
}
