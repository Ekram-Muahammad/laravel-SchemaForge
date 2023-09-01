<?php

namespace Ekram\SchemaForge;

use Ekram\SchemaForge\Commands\CrudCommand;
use Ekram\SchemaForge\Commands\CloneCommand;

use Illuminate\Support\ServiceProvider;

class SchemaForgeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

        $this->commands([
            CrudCommand::class,
            CloneCommand::class
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
