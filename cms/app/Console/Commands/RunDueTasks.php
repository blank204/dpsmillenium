<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Scheduling\DueTasks;
use Illuminate\Console\Command;

class RunDueTasks extends Command
{
    protected $signature = 'cms:due-tasks';

    protected $description = 'Publish scheduled posts, expire pins and empty the recycle bin';

    public function handle(DueTasks $tasks): int
    {
        $result = $tasks->run();
        Setting::put('scheduler.last_run_at', now()->toIso8601String());
        Setting::put('scheduler.last_source', 'cron');
        $this->info(json_encode($result));

        return self::SUCCESS;
    }
}
