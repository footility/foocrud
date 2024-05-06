<?php

namespace Footility\Foocrud\Services;


use Footility\Console\Commands\CrudCleanCommand;
use Footility\Console\Commands\CrudEntityCommand;
use Footility\Console\Commands\CrudGenerateCommand;
use Footility\Console\Commands\CrudInstallCommand;
use Illuminate\Support\ServiceProvider;

class FooCrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudInstallCommand::class,
                CrudGenerateCommand::class,
                CrudEntityCommand::class,
                CrudCleanCommand::class,
            ]);
        }
    }

}
