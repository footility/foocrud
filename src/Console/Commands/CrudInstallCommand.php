<?php

namespace Footility\Foocrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CrudInstallCommand extends Command
{
  protected $signature = 'foo:crud-install';
  protected $description = 'Install the CRUD entities and fields tables';

  public function handle()
  {
    if (Schema::hasTable('foo_entities')) {
      $this->info('FooCrud is already installed.');
    } else {
      Schema::create('foo_entities', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique(); // Assicura che il nome sia unico
        $table->timestamps();
      });

      $this->info('The foo_entities table has been created successfully.');
    }

    if (!Schema::hasTable('foo_fields')) {
      Schema::create('foo_fields', function (Blueprint $table) {
        $table->id();
        $table->foreignId('entity_id')->constrained('foo_entities')->onDelete('cascade');
        $table->string('name');
        $table->string('type');
        $table->timestamps();
      });

      $this->info('The foo_fields table has been created successfully.');
    } else {
      $this->info('The foo_fields table already exists.');
    }
  }
}
