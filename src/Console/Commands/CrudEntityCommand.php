<?php

namespace Footility\Foocrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrudEntityCommand extends Command
{
  protected $signature = 'foo:crud-entity {name} {fields*}';
  protected $description = 'Register a new CRUD entity with fields';

  public function handle()
  {
    $entityName = $this->argument('name');
    $fields = $this->argument('fields');
    $entityNameStudly = Str::studly($entityName);

    if (!Schema::hasTable('foo_entities')) {
      $this->error('You must run "php artisan foo:crud-install" before using this command.');
      return;
    }

    // Verifica l'unicità dell'entità (case insensitive)
    $exists = DB::table('foo_entities')
      ->whereRaw('lower(name) = ?', [strtolower($entityNameStudly)])
      ->exists();

    if ($exists) {
      $this->error("The entity '{$entityNameStudly}' is already registered.");
      return;
    }

    $entityId = DB::table('foo_entities')->insertGetId([
      'name' => $entityNameStudly,
      'created_at' => now(),
      'updated_at' => now()
    ]);

    foreach ($fields as $field) {
      [$name, $type] = explode(':', $field);

      DB::table('foo_fields')->insert([
        'entity_id' => $entityId,
        'name' => $name,
        'type' => $type,
        'created_at' => now(),
        'updated_at' => now()
      ]);
    }

    $this->info("Entity '{$entityNameStudly}' registered successfully with fields.");
  }
}
