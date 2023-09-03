<?php

namespace Ekram\SchemaForge\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Reset {
    protected $tableName;

    function __construct( $tableName ) {
        $this->tableName = $tableName;
        $this->deleteMigration();
        $this->deleteTable();
        $this->deleteModel();
        $this->deleteViews();
        $this->deleteSeeder();
        $this->deleteFactory();
        $this->deleteController();
        $this->deleteRoutes();
        $this->deleteSidebarLink();
    }

    public function deleteMigration() {
        $migrationFiles = File::glob( database_path( 'migrations/*.php' ) );

        foreach ( $migrationFiles as $migrationFile ) {
            $migrationContents = file_get_contents( $migrationFile );
            $migrationName=$this->tableName;
            if ( strpos( $migrationContents, "Schema::create('$migrationName'" ) !== false ) {
                File::delete( $migrationFile );
            }
        }
    }

    public function deleteTable() {
        if ( Schema::hasTable( $this->tableName ) ) {
            Schema::dropIfExists( $this->tableName );
        }
    }

    public function deleteModel() {
        $modelPath = app_path( 'Models/' . Str::ucfirst( $this->tableName ) . '.php' );
        if ( File::exists( $modelPath ) ) {
            File::delete( $modelPath );
        }
    }

    public function deleteViews() {
        $viewsPath = resource_path( 'views/pages/' .$this->tableName );
        if ( file_exists( $viewsPath ) ) {
            File::deleteDirectory( $viewsPath );
        }
    }

    public function deleteSeeder() {
        $modelClassName = Str::ucfirst( $this->tableName );
        $seederClassName = "{$modelClassName}Seeder";
        $seederFilePath = database_path( "seeders/{$seederClassName}.php" );
        if ( file_exists( $seederFilePath ) ) {
            File::delete( $seederFilePath );

            // Update DatabaseSeeder
            $databaseSeederPath = database_path( 'seeders/DatabaseSeeder.php' );
            $databaseSeederContent = file_get_contents( $databaseSeederPath );
            $newContent = str_replace( "\$this->call({$seederClassName}::class);", '', $databaseSeederContent );
            file_put_contents( $databaseSeederPath, $newContent );
        }
    }

    public function deleteFactory() {

        $modelClassName = Str::ucfirst( $this->tableName );
        $factoryClassName = "{$modelClassName}Factory";

        $factoryFilePath = database_path( "factories/{$factoryClassName}.php" );
        if ( file_exists( $factoryFilePath ) ) {
            File::delete( $factoryFilePath );
        }
    }

    public function deleteController() {
        $controllerClassName = ucfirst( Str::camel( Str::singular( $this->tableName ) ) ) . 'Controller';
        $controllerFilePath = app_path( "Http/Controllers/{$controllerClassName}.php" );
        if ( file_exists( $controllerFilePath ) ) {
            File::delete( $controllerFilePath );
        }
        $apiControllerFilePath = app_path( "Http/Controllers/Api/{$controllerClassName}.php" );
        if ( file_exists( $apiControllerFilePath ) ) {
            File::delete( $apiControllerFilePath );
        }
    }

    public function deleteRoutes() {
        $apiRouteFile =  base_path( 'routes/api.php' );
        $webRouteFile = base_path( 'routes/web.php' );

        $routePrefix = $this->tableName;

        // delete api routes

        $controllerClassName = ucfirst( Str::camel( Str::singular( $this->tableName ) ) ) . 'Controller';

        $apiFileContents = file_get_contents( $apiRouteFile );

        $startPosition = strpos( $apiFileContents, "Route::group(['prefix' => '{$routePrefix}']" );

        $endPosition = strpos( $apiFileContents, '});', $startPosition ) + 3;

        if ( $startPosition !== false && $endPosition !== false ) {
            $newContents = substr_replace( $apiFileContents, '', $startPosition, $endPosition - $startPosition );

            file_put_contents( $apiRouteFile, $newContents );

            $importStatement = "use App\\Http\\Controllers\\Api\\{$controllerClassName};";

            $importPosition = strpos($newContents, $importStatement);

            if ($importPosition !== false) {
                // Remove the import statement
                $apiContent = substr_replace($newContents, '', $importPosition, strlen($importStatement));

                file_put_contents($apiRouteFile, $apiContent);
            }
        }

        // delete web routes

        $webFileContents = file_get_contents( $webRouteFile );

        $importStatement = "use App\\Http\\Controllers\\$controllerClassName;";

        $startPosition = strpos( $webFileContents, "Route::resource('{$routePrefix}'" );

        $endPosition = strpos( $webFileContents, '::class);', $startPosition ) + 9;

        if ( $startPosition !== false && $endPosition !== false ) {
            // Remove the route group from the routes file
            $newContent = substr_replace( $webFileContents, '', $startPosition, $endPosition - $startPosition );

            $newContents = str_replace( $importStatement, '', $newContent );

            // Save the updated contents back to the routes file
            file_put_contents( $webRouteFile, $newContent );

            $importStatement = "use App\\Http\\Controllers\\{$controllerClassName};";

            $importPosition = strpos($newContent, $importStatement);

            if ($importPosition !== false) {
                // Remove the import statement
                $webContent = substr_replace($newContent, '', $importPosition, strlen($importStatement));

                file_put_contents($webRouteFile, $webContent);
            }
        }

    }

    public function deleteSidebarLink() {
        $dashboardPath = resource_path( 'views/layouts/dashboard.blade.php' );

        if ( file_exists( $dashboardPath ) ) {

            $dashboardContent = file_get_contents( $dashboardPath );

            $dom = new \DOMDocument();
            $dom->loadHTML( $dashboardContent, LIBXML_NOERROR );

            $liElements = $dom->getElementsByTagName( 'li' );

            foreach ( $liElements as $liElement ) {
                $pElements = $liElement->getElementsByTagName( 'p' );
                foreach ( $pElements as $pElement ) {
                    if ( trim( $pElement->textContent ) === $this->tableName ) {
                        $liElement->parentNode->removeChild( $liElement );
                        break;
                        // Stop further processing if the element is removed
                    }
                }
            }

            $updatedContent = $dom->saveHTML();

            file_put_contents( $dashboardPath, $updatedContent );
        }
    }
}