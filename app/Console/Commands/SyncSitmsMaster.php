<?php

namespace App\Console\Commands;

use App\Jobs\SyncSitmsMasterJob;
use Illuminate\Console\Command;

class SyncSitmsMaster extends Command
{
    protected $signature = 'sitms:sync {--page=1} {--size=1000}';
    protected $description = 'Sync SITMS employees (read-only, paginated)';

    public function handle(): int
    {
        $page = (int) $this->option('page');
        $size = (int) $this->option('size');
        SyncSitmsMasterJob::dispatch($page, $size);
        $this->info("Queued SITMS sync — page={$page}, size={$size}");
        return self::SUCCESS;
    }
}
