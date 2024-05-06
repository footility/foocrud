<?php

namespace Footility\Foocrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

define('STUB_PATH', base_path('stubs/foo'));

class CrudGenerateCommand extends Command
{
    protected $signature = 'foo:crud-generate';
    protected $description = 'Generate a model with migration and resource controller';

    private $parseMap = [];

    public function __construct()
    {
        parent::__construct(); // Necessario per inizializzare correttamente il comando
        // Il resto dell'inizializzazione sarà fatto nel metodo handle per avere accesso agli argomenti
    }

    public function handle()
    {

        if (!Schema::hasTable('foo_entities')) {
            $this->error('You must run "php artisan foo:crud:install" and migrate before using this command.');
            return;
        }

        if (!Schema::hasTable('foo_entities') || !DB::table('foo_entities')->count()) {
            $this->error('No entities found or the foo_entities table does not exist.');
            return;
        }

        $entities = DB::table('foo_entities')->get();

        foreach ($entities as $entity) {
            $this->info("Generating CRUD for: {$entity->name}");
            $this->_handle($entity->name);

        }

        //alla fine di tutto eseguo la migrazione
        $this->runMigration();
        $this->generateConfiguration();
    }

    private function _handle(string $name)
    {
        $this->parseMap = [
            'entityName' => Str::studly($name),
            'entityNameVariable' => Str::camel($name),
            'entityNameRoute' => Str::plural(Str::snake($name)),
            'entityNameTable' => Str::snake(Str::plural($name)),
            'entityNameList' => Str::plural(Str::camel($name)),
            'table' => Str::snake(Str::plural($name)),
            'class' => Str::studly($name),
        ];

        $this->generateModel();
        $this->generateController();
        $this->generateMigration();
        $this->createBaseLayout();
        $this->generateViews();
        $this->ensureRoutingSetup();
        $this->writeFoocrudRoutes();

    }


    protected function generateConfiguration()
    {
        $configPath = config_path('foo_navigation.php');
        $navigation = File::exists($configPath) ? require $configPath : [];

        $entities = DB::table('foo_entities')->get();

        foreach ($entities as $entity) {
            // Trasforma il nome dell'entità per la label e la route
            $label = Str::title($entity->name);
            $route = Str::plural(Str::snake($entity->name));

            // Verifica se l'entità esiste già nel file di configurazione
            if (!array_search($label, array_column($navigation, 'label'))) {
                // Aggiungi l'entità alla configurazione
                $navigation[] = [
                    'label' => $label,
                    'route' => $route . ".index"
                ];
            }
        }

        // Esporta l'array aggiornato nel file di configurazione
        $exportData = "<?php\n\nreturn " . var_export($navigation, true) . ";\n";
        File::put($configPath, $exportData);

        $this->info("Navigation configuration updated successfully.");
    }


    protected function generateModel()
    {
        $modelPath = app_path("Models/{$this->parseMap['entityName']}.php");
        $this->generateFile($modelPath, 'model.stub');
    }

    protected function generateController()
    {
        $controllerPath = app_path("Http/Controllers/{$this->parseMap['entityName']}Controller.php");
        $this->generateFile($controllerPath, 'controller.model.stub');
    }

    protected function generateMigration()
    {
        $existingMigration = $this->findMigration();
        if ($existingMigration) {
            $this->info("Existing migration found: {$existingMigration}");
            $relativePath = 'database/migrations/' . basename($existingMigration);
            $rollbackResult = $this->call('migrate:rollback', ['--path' => $relativePath]);
            if ($rollbackResult === 0 && file_exists($existingMigration)) {
                unlink($existingMigration);
                $this->info("Old migration file removed.");
            } else {
                $this->error("Failed to rollback the migration.");
                return;
            }
        }
        $this->createNewMigration();
    }

    protected function createNewMigration()
    {
        $datePrefix = date('Y_m_d_His');
        $migrationPath = database_path("/migrations/{$datePrefix}_foo_create_{$this->parseMap['entityNameTable']}_table.php");
        $this->generateFile($migrationPath, 'migration.create.stub');
    }

    protected function createBaseLayout()
    {
        $layoutPath = resource_path('views/layouts/app.blade.php');
        if (!file_exists($layoutPath)) {
            $this->generateFile($layoutPath, 'layout.stub');
        }
    }

    protected function generateViews()
    {
        $viewsPath = resource_path("views/{$this->parseMap['entityNameRoute']}");
        if (!is_dir($viewsPath)) {
            mkdir($viewsPath, 0777, true);
        }
        foreach (['index', 'create', 'edit', 'show'] as $view) {
            $viewPath = "{$viewsPath}/{$view}.blade.php";
            $this->generateFile($viewPath, "{$view}.stub");
        }
    }

    protected function generateFile($filePath, $stubName)
    {
        $stub = file_get_contents(STUB_PATH . "/{$stubName}");
        foreach ($this->parseMap as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', $value, $stub);
        }
        file_put_contents($filePath, $stub);
        $this->info("File created: {$filePath}");
    }

    protected function findMigration()
    {
        $pattern = database_path("migrations/*_foo_create_{$this->parseMap['entityNameTable']}_table.php");
        $migrations = glob($pattern);
        return $migrations ? $migrations[0] : null;
    }

    protected function ensureRoutingSetup()
    {
        $webRoutePath = base_path('routes/web.php');
        $includeString = "require __DIR__ . '/foocrud.php';";
        if (strpos(file_get_contents($webRoutePath), $includeString) === false) {
            file_put_contents($webRoutePath, "\n" . $includeString, FILE_APPEND);
            $this->info("Updated web.php to include foocrud.php routes file.");
        }
    }

    protected function writeFoocrudRoutes()
    {
        $entities = DB::table('foo_entities')->get();
        $routeContent = "<?php\n\n";
        foreach ($entities as $entity) {
            $routeContent .= "Route::resource('" . Str::plural(Str::snake($entity->name)) . "', 'App\\Http\\Controllers\\" . $entity->name . "Controller');\n";
        }
        file_put_contents(base_path('routes/foocrud.php'), $routeContent);
        $this->info('Routes file has been generated.');
    }


    protected function runMigration()
    {
        $this->call('migrate');
        $this->info("Database migrations executed.");
    }
}
