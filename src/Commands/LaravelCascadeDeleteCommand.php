<?php

namespace Gigerit\LaravelCascadeDelete\Commands;

use Illuminate\Console\Command;

class LaravelCascadeDeleteCommand extends Command
{
    public $signature = 'laravel-cascade-delete';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
