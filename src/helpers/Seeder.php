<?php
namespace Ekram\SchemaForge\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Seeder {
    public function generateSeederFile( $tableName ) {
        $modelClassName = Str::ucfirst( $tableName );
        $seederClassName = "{$modelClassName}Seeder";

        // Generate the seeder file
        Artisan::call( 'make:seeder', [
            'name' => $seederClassName,
        ] );

        // add seeder to DatabseSeeder file
        $this->addToDatabaseSeeder( $seederClassName );

        return $seederClassName;
    }

    public function addToDatabaseSeeder( $seederClass ) {
        $databaseSeederPath = database_path( 'seeders/DatabaseSeeder.php' );

        if ( file_exists( $databaseSeederPath ) ) {
            $databaseSeederContent = file_get_contents( $databaseSeederPath );

            // Check if the seeder class is not already present in DatabaseSeeder
            if ( !Str::contains( $databaseSeederContent, $seederClass ) ) {
                // Add the seeder to the run() method in DatabaseSeeder
                $modifiedSeederContent = preg_replace(
                    '/(public function run\(\)(?:\s*:\s*void)?\s*\{)/',
                    "\$1\n        \$this->call({$seederClass}::class);\n        //",
                    $databaseSeederContent,
                    1, // Limit to replace only the first occurrence
                );

                file_put_contents( $databaseSeederPath, $modifiedSeederContent );
            } else {
              //  $this->info( "Seeder {$seederClass} is already present in DatabaseSeeder." );
            }
        }
    }

    public function generateSeederContent( $tableName, $fields, $numRows ) {
        $seederStub = file_get_contents( __DIR__ . '/stubs/seeder.stub' );
        $modelClassName = Str::studly( $tableName );

        $seederContent = str_replace( [ '{{tableName}}', '{{modelClassName}}', '{{numRows}}' ], [ Str::studly( $tableName ), $modelClassName, $numRows ], $seederStub );

        // Save the seeder content to a file
        $seederFileName = Str::studly( $tableName ) . 'Seeder';
        $seederFilePath = database_path( "seeders/{$seederFileName}.php" );

        file_put_contents( $seederFilePath, $seederContent );

        try {
            Artisan::call( 'db:seed' );
        } catch (\Throwable $th) {
            //throw $th;
        }
        
    }

}