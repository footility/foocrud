<?php

namespace Footility\Foocrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CrudInstallCommand extends Command
{
    protected $signature = 'foo:crud-install';
    protected $description = 'Install the CRUD entities table directly';

    public function handle()
    {
        if (Schema::hasTable('foo_entities')) {
            $this->info('FooCrud is already installed.');
            return;
        }

        Schema::create('foo_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Assicura che il nome sia unico
            $table->timestamps();
        });

        $this->info('The foo_entities table has been created successfully. You can now add CRUD entities.');
    }
}
