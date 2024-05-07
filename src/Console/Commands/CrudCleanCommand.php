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
        // Rimozione di file e configurazioni che non dipendono dalla presenza di entità
        $this->removeIndependentFiles();

        // Rimuove l'inclusione delle route di CRUD da web.php
        $this->removeRouteInclusion();

        // Controlla se la tabella delle entità esiste
        if (!Schema::hasTable('foo_entities')) {
            $this->warn('The foo_entities table does not exist.');
        } else {
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
        }

        $this->forcedRemoveLostMigrations();

        $this->info('All entities and related files have been cleaned up successfully.');
    }

    private function forcedRemoveLostMigrations()
    {
        $migrationFiles = File::glob(database_path('migrations/*_foo_*_table.php'));

        if (count($migrationFiles) > 0) {
            foreach ($migrationFiles as $migrationFile) {
                $migrationFilePath = substr($migrationFile, strlen(base_path()) + 1); // Removes base path to get relative path
                $this->info("Deleting lost migration file $migrationFilePath");
                File::delete($migrationFile);
            }
        } else $this->info('No migration file found for foo_entities table.');
    }

    protected function rollbackMigration()
    {
        $migrationFiles = File::glob(database_path('migrations/*create_foo_entities_table.php'));

        if (count($migrationFiles) > 0) {
            $migrationFile = last($migrationFiles); // Get the latest migration
            $migrationFilePath = substr($migrationFile, strlen(base_path()) + 1); // Removes base path to get relative path

            $rollbackResult = $this->call('migrate:rollback', ['--path' => $migrationFilePath]);
            return $rollbackResult === 0;
        }

        $this->error('No migration file found for foo_entities table.');
        return false;
    }

    protected function removeIndependentFiles()
    {
        $pathsToDelete = [
            config_path('foo_navigation.php'),
            resource_path('views/layouts/app.blade.php'),
            base_path('routes/foocrud.php'),
            base_path('stubs/foo/')
        ];

        // Aggiunta della rimozione di tutte le migrazioni create per foo_entities
        $migrationFiles = File::glob(database_path('migrations/*create_foo_entities_table.php'));
        $pathsToDelete = array_merge($pathsToDelete, $migrationFiles);

        foreach ($pathsToDelete as $path) {
            if (is_dir($path)) {
                File::deleteDirectory($path);
                $this->info("Deleted directory: {$path}");
            } elseif (File::exists($path)) {
                File::delete($path);
                $this->info("Deleted file: {$path}");
            } else {
                $this->error("Path not found: {$path}");
            }
        }
    }

    protected function removeEntityFiles($entityName)
    {
        $studlyName = \Illuminate\Support\Str::studly($entityName);
        $pluralSnakeName = \Illuminate\Support\Str::plural(\Illuminate\Support\Str::snake($entityName));

        $paths = [
            app_path("Models/{$studlyName}.php"),
            app_path("Http/Controllers/{$studlyName}Controller.php"),
            resource_path("views/{$pluralSnakeName}"),
            database_path("migrations/*_create_{$pluralSnakeName}_table.php")
        ];

        foreach ($paths as $path) {
            if (is_dir($path)) {
                File::deleteDirectory($path);
                $this->info("Deleted directory: {$path}");
            } elseif (File::exists($path)) {
                File::delete($path);
                $this->info("Deleted file: {$path}");
            } else {
                $this->error("Path not found: {$path}");
            }
        }

        DB::table('foo_entities')->where('name', $studlyName)->delete();
        $this->info("Entity {$studlyName} and all associated files have been removed successfully.");
    }

    protected function removeRouteInclusion()
    {
        $webRoutesPath = base_path('routes/web.php');
        $includeString = "require __DIR__ . '/foocrud.php';";

        if (File::exists($webRoutesPath)) {
            $content = File::get($webRoutesPath);
            if (strpos($content, $includeString) !== false) {
                // Rimuove la stringa di inclusione
                $updatedContent = str_replace($includeString, '', $content);
                File::put($webRoutesPath, $updatedContent);
                $this->info("Inclusion of 'foocrud.php' has been removed from web.php.");
            } else {
                $this->info("No inclusion of 'foocrud.php' found in web.php.");
            }
        } else {
            $this->error("web.php file not found.");
        }
    }


}
