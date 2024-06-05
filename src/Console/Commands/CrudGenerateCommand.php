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

    private $entityMap = [];
    private $fieldsMap = [];

    private $entity = null;
    private $entityFields = null;

    private $stubString = null;

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

        $this->entity = DB::table('foo_entities')->where('name', '=', $name)->first();
        $this->entityFields = DB::table('foo_fields')->where('entity_id', '=', $this->entity->id);

        $this->entityMap = [
            'entityName' => Str::studly($name),
            'entityNameVariable' => Str::camel($name),
            'entityNameRoute' => Str::plural(Str::snake($name)),
            'entityNameTable' => Str::snake(Str::plural($name)),
            'entityNameList' => Str::plural(Str::camel($name)),
            'table' => Str::snake(Str::plural($name)),
            'class' => Str::studly($name),
        ];


        $this->generateModel();
        $this->generateRequest();
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
        $navigation = [];
        $entities = DB::table('foo_entities')->get();

        foreach ($entities as $entity) {
            // Trasforma il nome dell'entità per la label e la route
            $label = Str::title($entity->name);
            $route = Str::plural(Str::snake($entity->name));

            // Aggiungi l'entità alla configurazione
            $navigation[] = [
                'label' => $label,
                'route' => $route . ".index"
            ];

        }

        // Esporta l'array aggiornato nel file di configurazione
        $exportData = "<?php\n\nreturn " . var_export($navigation, true) . ";\n";
        File::put($configPath, $exportData);

        $this->info("Navigation configuration updated successfully.");
    }

    public function generateRequest()
    {

    }


    protected function generateModel()
    {
        $modelPath = app_path("Models/{$this->entityMap['entityName']}.php");
        $this->openStub('model');

        $fields = $this->entityFields->pluck('name')->toArray();
        $fields = join(",",$fields);
        $this->entityMap['fields'] = $fields;

        $this->parseStub();
        $this->publishStub($modelPath);

    }


    protected function generateController()
    {
        $controllerPath = app_path("Http/Controllers/{$this->entityMap['entityName']}Controller.php");
        $this->openStub('controller');
        $this->parseStub();
        $this->publishStub($controllerPath);
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

        $this->openStub('migration');
        $this->parseStub();

        $datePrefix = date('Y_m_d_His');
        $migrationPath = database_path("/migrations/{$datePrefix}_foo_create_{$this->entityMap['entityNameTable']}_table.php");
        $this->publishStub($migrationPath);
    }

    protected function createBaseLayout()
    {
        $layoutPath = resource_path('views/layouts/app.blade.php');
        $this->openStub('layout');
        $this->parseStub();
        $this->publishStub($layoutPath);

    }

    protected function generateViews()
    {
        $viewsPath = resource_path("views/{$this->entityMap['entityNameRoute']}");

        if (!is_dir($viewsPath)) {
            mkdir($viewsPath, 0777, true);
        }

        foreach (['index', 'create', 'edit', 'show'] as $view) {
            $viewPath = "{$viewsPath}/{$view}.blade.php";
            $this->openStub($view);
            $this->parseStub();
            $this->publishStub($viewPath);
        }
    }

    protected function openStub($stubName)
    {
        $stubName = $stubName . ".stub";
        //stub del pacchetto
        $stubPath = base_path('vendor/footility/foocrud/src/stubs/' . $stubName);

        //stub pubblicati dall'utente
        $publishedStubPath = resource_path('/stubs/foo/' . $stubName);

        //controllo quale file prendere
        if (file_exists($publishedStubPath)) {
            $stubPath = $publishedStubPath;
        }

        if (!file_exists($stubPath)) {
            $this->error("The stub file does not exist: {$stubPath}");
            return;
        }

        $this->stubString = file_get_contents($stubPath);

    }

    protected function publishStub($filePath)
    {
        file_put_contents($filePath, $this->stubString);
        $this->info("File created: {$filePath}");
    }


    protected function findMigration()
    {
        $pattern = database_path("migrations/*_foo_create_{$this->entityMap['entityNameTable']}_table.php");
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

    private function generateMigrationFields()
    {
        $fields = $this->entityFields->get();

        $fieldsPlaceholder = '';
        foreach ($fields as $field) {
            $fieldsPlaceholder .= "\$table->{$field->type}('{$field->name}');\n";
        }
        return $fieldsPlaceholder;
    }

    /**
     * @param string $stubPath
     * @return array|false|string|string[]
     */
    private function parseStub()
    {
        foreach ($this->entityMap as $key => $value) {
            $this->stubString = str_replace('{{ ' . $key . ' }}', $value, $this->stubString);
        }

    }
}
