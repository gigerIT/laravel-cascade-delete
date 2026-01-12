<?php

declare(strict_types=1);

namespace Gigerit\LaravelCascadeDelete\Commands;

use Gigerit\LaravelCascadeDelete\Support\Morph;
use Illuminate\Console\Command;

class CascadeDeleteCleanCommand extends Command
{
    public $signature = 'cascade-delete:clean {--dry-run : Only count the records that would be deleted}';

    public $description = 'Clean residual polymorphic relationships';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run mode enabled. No records will be deleted.');
        }

        $morph = new Morph();
        $deleted = $morph->clearOrphanAllModels($dryRun);

        $message = $dryRun
            ? sprintf('Found %d residual polymorphic records.', $deleted)
            : sprintf('Deleted %d residual polymorphic records.', $deleted);

        $this->info($message);

        return self::SUCCESS;
    }
}
