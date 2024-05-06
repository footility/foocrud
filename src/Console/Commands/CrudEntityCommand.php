<?php
namespace Footility\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CrudEntityCommand extends Command
{
    protected $signature = 'foo:crud-entity {name}';
    protected $description = 'Register a new CRUD entity';

    public function handle()
    {
        $entityName = $this->argument('name');
        $entityNameStudly = Str::studly($entityName);

        if (!\Schema::hasTable('foo_entities')) {
            $this->error('You must run "php artisan foo:crud:install" and migrate before using this command.');
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

        DB::table('foo_entities')->insert([
            'name' => $entityNameStudly,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->info("Entity '{$entityNameStudly}' registered successfully.");
    }
}
