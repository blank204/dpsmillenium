<?php

namespace Tests\Feature\Audit;

use App\Audit\AuditChain;
use App\Audit\AuditLogger;
use App\Support\Actor;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\IndependentConnections;
use Tests\TestCase;

/**
 * Ledger T3 / review Minor 3: two writers must never both read the same chain
 * head (a fork would make "Verify log integrity" report tampering). The head is
 * a single locked row, so the second writer waits even when the log is empty.
 */
class AuditConcurrencyTest extends TestCase
{
    use IndependentConnections;
    use RefreshDatabase;

    public function test_chain_head_row_exists_after_migration(): void
    {
        $head = DB::table('audit_chain_head')->where('id', 1)->first();
        $this->assertNotNull($head);
        $this->assertSame(AuditLogger::GENESIS, $head->hash);
    }

    public function test_head_follows_each_write(): void
    {
        $entry = app(AuditLogger::class)->record('test.one', Actor::system());
        $head = DB::table('audit_chain_head')->where('id', 1)->first();
        $this->assertSame($entry->hash, $head->hash);
        $this->assertSame($entry->id, (int) $head->last_entry_id);
    }

    public function test_second_writer_waits_for_the_head_even_on_an_empty_log_then_chains_onto_the_first(): void
    {
        $a = $this->independentConnection('audit_a');
        $b = $this->independentConnection('audit_b');
        $baselineId = (int) $a->table('audit_entries')->max('id');
        $headBefore = (array) $a->table('audit_chain_head')->where('id', 1)->first();
        $logger = app(AuditLogger::class);

        try {
            // Writer A has read (locked) the head but not yet inserted: the exact
            // window where "ORDER BY id DESC LIMIT 1 FOR UPDATE" on an empty table
            // let a second writer read the same (missing) tail and fork the chain.
            $a->beginTransaction();
            $this->onConnection('audit_a', fn () => $logger->lockHead());

            try {
                $this->onConnection('audit_b', fn () => $logger->record('test.second', Actor::system()));
                $this->fail('the second writer did not wait for the chain head lock');
            } catch (QueryException $e) {
                $this->assertStringContainsStringIgnoringCase('lock wait timeout', $e->getMessage());
            }

            $first = $this->onConnection('audit_a', fn () => $logger->record('test.first', Actor::system()));
            $a->commit();

            $second = $this->onConnection('audit_b', fn () => $logger->record('test.second', Actor::system()));

            $this->assertSame($first->hash, $second->prev_hash);
            $this->assertNull($this->onConnection('audit_b', fn () => app(AuditChain::class)->firstBrokenId()));
        } finally {
            $this->closeIndependentConnections();
            $cleanup = $this->independentConnection('audit_cleanup');
            $cleanup->table('audit_entries')->where('id', '>', $baselineId)->delete();
            $cleanup->table('audit_chain_head')->where('id', 1)->update($headBefore);
            $this->closeIndependentConnections();
        }
    }
}
