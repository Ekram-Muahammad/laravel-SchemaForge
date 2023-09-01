<?php

namespace Ekram\SchemaForge\Commands;
use Ekram\SchemaForge\Helpers\ApiController;
use Ekram\SchemaForge\Helpers\Factory;
use Ekram\SchemaForge\Helpers\ResoureController;
use Ekram\SchemaForge\Helpers\Seeder;
use Ekram\SchemaForge\Helpers\Views;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Ekram\SchemaForge\Helpers\Migration;
use Ekram\SchemaForge\Helpers\Model;
use Ekram\SchemaForge\Helpers\Field;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ekram\SchemaForge\Helpers\Reset;

class CrudCommand extends Command {
    protected $signature = 'make:crud {jsonFile} {action?}';

    protected $description = 'Create a Crud Operation';

    public function handle() {

        $fields = [];

        $relationships = [];

        $jsonFile = $this->argument( 'jsonFile' );

        if ( file_exists( base_path( '/cruds/' . $jsonFile . '.json' ) ) ) {

            try {
                //code...
                $conntection = DB::connection()->getPdo();
            } catch ( \Throwable $th ) {
                $this->error( 'The Database connection is  unavailable' );
                return;
            }

            $jsonFile = file_get_contents( base_path( '/cruds/' . $jsonFile . '.json' ) );

            $jsonData = json_decode( $jsonFile, true );

            $tableName = $jsonData[ 'tableName' ];

            $action = $this->argument( 'action' ) ?? 'none';

            if ( $action == 'reset' ) {
                new Reset( $tableName );

                return;
            } else if ( $action == 'update' ) {
                new Reset( $tableName );
            }

           // if ( !Schema::hasTable( $tableName ) ) {

                $modelName = Str::ucfirst( $tableName );

                $columns = $jsonData[ 'columns' ];

                $field = new Field();
                $data = $field->generateFields( $columns );

                $fields = $data[ 'fields' ];
                $relationships = $data[ 'relationships' ];

                if ( !empty( $fields ) ) {

                    if ($jsonData[ 'migration']) {
                        $migrationController = new Migration();
                        $migrationController->generateMigrationContent( $tableName, $fields );
                    }

                    $modelController = new Model();

                    $modelController->generateModel( $tableName, $fields, $modelName, $relationships );

                    // create seeder

                    $createSeeder = $jsonData[ 'seeder' ] ?? false;

                    if ( $createSeeder ) {
                        $numRows = $jsonData[ 'seederNumRows' ] ?? 10;

                        // Generate the factory for the given table

                        $factory = new Factory();

                        $factory->generateFactory( $tableName, Str::ucfirst( $tableName ), $fields );

                        $factory->addHasFactoryTrait( $tableName );

                        // Generate the seeder file
                        
                        $seeder = new Seeder();
                        $seeder->generateSeederFile( $tableName );
                        // Generate the seeder file content
                        $seeder->generateSeederContent( $tableName, $fields, $numRows );
                    }

                    $createController = $jsonData[ 'resourceController' ] ?? false;

                    $createBladeView = $jsonData[ 'views' ] ?? false;

                    if ( $createController || $createBladeView ) {
                        $resourceController = new ResoureController();
                        $resourceController->generateController( $tableName, $fields, $modelName );
                    }

                    $createApiController = $jsonData[ 'apiController' ] ?? false;

                    if ( $createApiController ) {
                        $apiController = new ApiController();
                        $apiController->generateApiController( $tableName, $fields, $modelName );
                    }

                  
                    if ( $createBladeView ) {
                        $views = new Views();
                        $views->generateBladeViews( $tableName, $fields );
                        $views->generateFormFields( $fields, $tableName );
                    }

                    Artisan::call( 'optimize:clear' );

                    $this->info( 'all requirements created successfully' );

                } else {
                    $this->error( 'Not Fields provided' );
                }
            //} 
            // else {
            //     $this->error( "The table $tableName exists in the database." );
            // }

        } else {
            $this->error( 'The requested file is currently unavailable' );
        }
    }

}
