<?php

namespace Footility\Foocrud\Services;


use Footility\Foocrud\Console\Commands\CrudCleanCommand;
use Footility\Foocrud\Console\Commands\CrudEntityCommand;
use Footility\Foocrud\Console\Commands\CrudGenerateCommand;
use Footility\Foocrud\Console\Commands\CrudInstallCommand;
use Illuminate\Support\ServiceProvider;

class FooCrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'foocrud');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudInstallCommand::class,
                CrudGenerateCommand::class,
                CrudEntityCommand::class,
                CrudCleanCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../stubs' => resource_path('stubs/foo'),
            ], 'foocrud-stubs');
        }


    }

}
