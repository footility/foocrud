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
        $this->call('migrate:rollback', ['--path' => 'database/migrations/create_foo_entities_table.php']);

        // Elimina la tabella delle entità
        Schema::dropIfExists('foo_entities');

        $this->info('All entities and related files have been cleaned up successfully.');
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
        }

        if (File::exists($controllerPath)) {
            File::delete($controllerPath);
        }

        if (File::isDirectory($viewsPath)) {
            File::deleteDirectory($viewsPath);
        }

        // Trova e rimuovi tutte le migrazioni corrispondenti
        $migrations = File::glob($migrationPath);
        foreach ($migrations as $file) {
            File::delete($file);
        }

        // Rimuovi l'entità dalla tabella
        DB::table('foo_entities')->where('name', $studlyName)->delete();

        $this->info("Files and directories for {$studlyName} have been removed.");
    }
}
