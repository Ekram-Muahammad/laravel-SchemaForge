<?php

namespace Ekram\ArtisanCrud\Commands;
use Ekram\ArtisanCrud\Helpers\ApiController;
use Ekram\ArtisanCrud\Helpers\Factory;
use Ekram\ArtisanCrud\Helpers\ResoureController;
use Ekram\ArtisanCrud\Helpers\Seeder;
use Ekram\ArtisanCrud\Helpers\Views;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Ekram\ArtisanCrud\Helpers\Migration;
use Ekram\ArtisanCrud\Helpers\Model;
use Ekram\ArtisanCrud\Helpers\Field;

class CrudCommand extends Command {
    protected $signature = 'make:crud {jsonFile}';

    protected $description = 'Create a Crud Operation';

    public function handle() {

        $fields = [];

        $relationships = [];

        $jsonFile = $this->argument( 'jsonFile' );

        $jsonFile = file_get_contents( base_path( '/cruds/' . $jsonFile . '.json' ) );

        $jsonData = json_decode( $jsonFile, true );

        $tableName = $jsonData[ 'tableName' ];

        $modelName = Str::ucfirst( $tableName );

        $columns = $jsonData[ 'columns' ];

        $field = new Field();
        $data = $field->generateFields( $columns );

        $fields = $data[ 'fields' ];
        $relationships = $data[ 'relationships' ];

        if ( !empty( $fields ) ) {

            $migrationController = new Migration();

            $migrationController->generateMigrationContent( $tableName, $fields );

            $modelController = new Model();

            $modelController->generateModel( $tableName, $fields, $modelName, $relationships );

            // create seeder

            $createSeeder = $jsonData[ 'createSeeder' ] ?? false;

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

            if ( $createController ) {
                $resourceController = new ResoureController();
                $resourceController->generateController( $tableName, $fields, $modelName );
            }

            $createApiController = $jsonData[ 'apiController' ] ?? false;

            if ( $createApiController ) {
                $apiController = new ApiController();
                $apiController->generateApiController( $tableName, $fields, $modelName );
            }

            $createBladeView = $jsonData[ 'views' ] ?? false;

            if ( $createBladeView ) {
                $views=new Views();
                $views->generateBladeViews( $tableName, $fields );
                $views->generateFormFields( $fields, $tableName );
            }

            Artisan::call( 'optimize:clear' );

            $this->info("all requirements created successfully");

        } else {
            $this->error( 'No fields provided. Migration creation aborted.' );
        }
    }

}
