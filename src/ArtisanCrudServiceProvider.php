<?php

namespace Ekram\ArtisanCrud;

use Ekram\ArtisanCrud\Commands\CrudCommand;
use Ekram\ArtisanCrud\Commands\CloneCommand;

use Illuminate\Support\ServiceProvider;

class ArtisanCrudServiceProvider extends ServiceProvider
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
