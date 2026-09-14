<?php

namespace App\Console\Commands;

use App\Audit\AuditChain;
use Illuminate\Console\Command;

class AuditVerify extends Command
{
    protected $signature = 'cms:audit-verify';

    protected $description = 'Check that no audit log entry has been altered';

    public function handle(AuditChain $chain): int
    {
        $broken = $chain->firstBrokenId();
        if ($broken === null) {
            $this->info('Audit log intact.');
            return self::SUCCESS;
        }
        $this->error("Audit log altered at or before entry #{$broken}.");
        return self::FAILURE;
    }
}
