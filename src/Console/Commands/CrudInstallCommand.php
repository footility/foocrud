<?php

namespace Footility\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CrudInstallCommand extends Command
{
    protected $signature = 'foo:crud-install';
    protected $description = 'Install the CRUD entities table';

    public function handle()
    {
        if (Schema::hasTable('foo_entities')) {
            $this->info('FooCrud is already installed.');
            return;
        }


        $this->call('make:migration', [
            'name' => 'create_foo_entities_table',
            '--create' => 'foo_entities'
        ]);

        $this->info('Migration for foo_entities table has been created. Please run "php artisan migrate" to apply.');
    }
}
