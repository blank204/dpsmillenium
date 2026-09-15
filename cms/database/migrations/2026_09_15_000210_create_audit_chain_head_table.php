<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-row table holding the audit chain head. Every audit write locks this row
 * (primary-key SELECT ... FOR UPDATE), which serialises writers identically on
 * MySQL 8 and MariaDB 10.6+, including the very first write on an empty log.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_chain_head', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_entry_id')->nullable();
            $table->char('hash', 64);
        });

        $last = DB::table('audit_entries')->orderByDesc('id')->first();
        DB::table('audit_chain_head')->insert([
            'id' => 1,
            'last_entry_id' => $last?->id,
            'hash' => $last?->hash ?? str_repeat('0', 64),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain_head');
    }
};
