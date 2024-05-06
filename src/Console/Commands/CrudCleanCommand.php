<?php

namespace Footility\Foocrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CrudCleanCommand extends Command
{
    protected $signature = 'foo:crud-clean';
    protected $description = 'Clean all entities and remove generated files and tables';

    public function handle()
    {
        // Controlla se la tabella delle entità esiste
        if (!Schema::hasTable('foo_entities')) {
            $this->error('The foo_entities table does not exist.');
            return;
        }

        // Recupera tutte le entità
        $entities = DB::table('foo_entities')->get();

        // Rimuovi file e directory per ogni entità
        foreach ($entities as $entity) {
            $this->removeEntityFiles($entity->name);
        }

        // Esegui la migrazione di rollback
        if (!$this->rollbackMigration()) {
            $this->error('Failed to rollback the migration for foo_entities table.');
        }

        // Elimina la tabella delle entità
        Schema::dropIfExists('foo_entities');

        $this->info('All entities and related files have been cleaned up successfully.');
    }

    protected function rollbackMigration()
    {
        $migrationFiles = File::glob(database_path('migrations/*create_foo_entities_table.php'));
        if (count($migrationFiles) > 0) {
            $migrationFile = last($migrationFiles); // Get the latest migration
            $rollbackResult = $this->call('migrate:rollback', ['--path' => $migrationFile]);
            return $rollbackResult === 0;
        }
        $this->error('No migration file found for foo_entities table.');
        return false;
    }

    protected function removeEntityFiles($entityName)
    {
        $studlyName = \Illuminate\Support\Str::studly($entityName);
        $snakeName = \Illuminate\Support\Str::snake($entityName);
        $pluralSnakeName = \Illuminate\Support\Str::plural($snakeName);

        // Rimuovi i file di modello, controller, viste e migrazioni
        $modelPath = app_path("Models/{$studlyName}.php");
        $controllerPath = app_path("Http/Controllers/{$studlyName}Controller.php");
        $viewsPath = resource_path("views/{$pluralSnakeName}");
        $migrationPath = database_path("migrations/*_create_{$pluralSnakeName}_table.php");

        if (File::exists($modelPath)) {
            File::delete($modelPath);
        } else {
            $this->warn("Model file for {$studlyName} does not exist.");
        }

        if (File::exists($controllerPath)) {
            File::delete($controllerPath);
        } else {
            $this->warn("Controller file for {$studlyName} does not exist.");
        }

        if (File::isDirectory($viewsPath)) {
            File::deleteDirectory($viewsPath);
        } else {
            $this->warn("Views directory for {$studlyName} does not exist.");
        }

        // Trova e rimuovi tutte le migrazioni corrispondenti
        $migrations = File::glob($migrationPath);
        if (!empty($migrations)) {
            foreach ($migrations as $file) {
                File::delete($file);
            }
        } else {
            $this->warn("No migration files found for {$studlyName}.");
        }

        // Rimuovi l'entità dalla tabella
        DB::table('foo_entities')->where('name', $studlyName)->delete();
    }
}
