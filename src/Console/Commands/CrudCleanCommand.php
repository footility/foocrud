<?php

namespace Footility\Foocrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CrudCleanCommand extends Command
{
    protected $signature = 'foo:crud-clean';
    protected $description = 'Clean all entities and remove generated files and tables, effectively uninstalling the CRUD setup';

    public function handle()
    {
        // Verifica se la tabella delle entità esiste
        if (!Schema::hasTable('foo_entities')) {
            $this->error('The foo_entities table does not exist.');
            return;
        }

        // Recupera e rimuove i file per ogni entità
        $entities = DB::table('foo_entities')->get();
        foreach ($entities as $entity) {
            $this->removeEntityFiles($entity->name);
        }

        // Rimuovi il file delle rotte se esiste
        $this->removeRoutesFile();

        // Esegui la migrazione di rollback se possibile, altrimenti rimuovi direttamente la tabella
        if (!$this->rollbackMigration()) {
            Schema::dropIfExists('foo_entities');
            $this->info('Foo entities table has been dropped.');
        }

        $this->info('CRUD setup has been cleaned up successfully.');
    }

    protected function removeEntityFiles($entityName)
    {
        $studlyName = \Illuminate\Support\Str::studly($entityName);
        $snakeName = \Illuminate\Support\Str::snake($entityName);
        $pluralSnakeName = \Illuminate\Support\Str::plural($snakeName);

        // File paths
        $modelPath = app_path("Models/{$studlyName}.php");
        $controllerPath = app_path("Http/Controllers/{$studlyName}Controller.php");
        $viewsPath = resource_path("views/{$pluralSnakeName}");

        // Remove files if they exist
        File::delete($modelPath);
        File::delete($controllerPath);
        File::deleteDirectory($viewsPath);

        // Remove migrations
        $migrationFiles = File::glob(database_path("migrations/*_create_{$pluralSnakeName}_table.php"));
        foreach ($migrationFiles as $file) {
            File::delete($file);
        }

        // Remove entity from the table
        DB::table('foo_entities')->where('name', $studlyName)->delete();
    }

    protected function removeRoutesFile()
    {
        $routesFilePath = base_path('routes/foo_crud.php');
        if (File::exists($routesFilePath)) {
            File::delete($routesFilePath);
        }
    }

    protected function rollbackMigration()
    {
        $migrationFiles = File::glob(database_path('migrations/*create_foo_entities_table.php'));
        if (count($migrationFiles) > 0) {
            $migrationFile = last($migrationFiles); // Get the latest migration
            $rollbackResult = $this->call('migrate:rollback', ['--path' => $migrationFile]);
            return $rollbackResult === 0;
        }
        $this->warn('No migration file found for foo_entities table.');
        return false;
    }
}
