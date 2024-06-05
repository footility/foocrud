<?php

namespace Footility\Foocrud\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class FooCrudController extends Controller
{
    public function index()
    {
        $entities = DB::table('foo_entities')->get();
        return view('foocrud::dashboard', compact('entities'));
    }

    public function createEntity(Request $request)
    {
        $entityName = Str::studly($request->input('name'));
        DB::table('foo_entities')->insert(['name' => $entityName, 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->route('foocrud.dashboard');
    }

    public function addField(Request $request, $entityId)
    {
        $name = $request->input('name');
        $type = $request->input('type');
        DB::table('foo_fields')->insert(['entity_id' => $entityId, 'name' => $name, 'type' => $type, 'created_at' => now(), 'updated_at' => now()]);
        return redirect()->route('foocrud.dashboard');
    }

    public function generateFiles()
    {
        $entities = DB::table('foo_entities')->get();

        foreach ($entities as $entity) {
            $entityName = Str::studly($entity->name);
            $fields = DB::table('foo_fields')->where('entity_id', $entity->id)->get()->toArray();
            $fieldNames = array_map(function ($field) {
                return $field->name;
            }, $fields);

            $this->generateModel($entityName, $fieldNames);
            $this->generateController($entityName);
            $this->generateMigration($entityName, $fields);
        }

        return redirect()->route('foocrud.dashboard')->with('success', 'Files generated successfully.');
    }

    protected function generateModel($entityName, $fields)
    {
        $modelPath = app_path("Models/{$entityName}.php");
        $content = View::make('foocrud::stubs.model', compact('entityName', 'fields'))->render();
        File::put($modelPath, $content);
    }

    protected function generateController($entityName)
    {
        $controllerPath = app_path("Http/Controllers/{$entityName}Controller.php");
        $content = View::make('foocrud::stubs.controller', compact('entityName'))->render();
        File::put($controllerPath, $content);
    }

    protected function generateMigration($entityName, $fields)
    {
        $table = Str::snake(Str::plural($entityName));
        $fieldsArray = array_map(function ($field) {
            return ['name' => $field->name, 'type' => $field->type];
        }, $fields);
        $content = View::make('foocrud::stubs.migration', compact('table', 'fieldsArray'))->render();

        $datePrefix = date('Y_m_d_His');
        $migrationPath = database_path("/migrations/{$datePrefix}_create_{$table}_table.php");
        File::put($migrationPath, $content);
    }
}
