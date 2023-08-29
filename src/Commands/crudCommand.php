<?php

namespace Ekram\ArtisanCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Ekram\ArtisanCrud\Helpers\Migration;
use Ekram\ArtisanCrud\Helpers\Model;

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

        foreach ( $columns as $column ) {

            $fieldProperties = [];
            $fieldName = $column[ 'name' ];
            $fieldType = $column[ 'type' ];
            $defaultValue = $column[ 'defaultValue' ];
            $nullable = $column[ 'nullable' ];
            $unique = $column[ 'unique' ];
            $index = $column[ 'index' ];

            if ( in_array( $fieldType, [ 'string', 'char', 'text' ] ) ) {
                $fieldProperties[ 'length' ] = $column[ 'length' ];
            } elseif ( $fieldType === 'decimal' ) {
                $fieldProperties[ 'precision' ] = $column[ 'length' ];
                $fieldProperties[ 'scale' ] = $column[ 'precision' ];
            } elseif ( $fieldType === 'enum' ) {
                $enumValues = $column[ 'values' ];
                $fieldProperties[ 'enumValues' ] = explode( ',', $enumValues );
            }

            $hasRelation = false;

            $isForeignKey = $fieldType == 'foreign' ? true : false;

            if ( $isForeignKey ) {
                $hasRelation = true;
            } else {
                // to morph
                $hasRelation = $column[ 'hasRelation' ];
            }

            if ( $hasRelation ) {
                // generate model relations
                $relationType = $column[ 'relationType' ];

                $relatedTable = '';
                $primaryKey = '';

                if ( $relationType == 'morphTo' ) {
                    $relatedModelName = $fieldName;
                } elseif ( $relationType == 'hasManyThrough' ) {
                    $intermediateModel = $column[ 'intermediateModel' ];
                    $targetModel = $column[ 'targetModel' ];
                    $relatedModelName = $fieldName;
                } else {
                    $relatedTable = $column[ 'relatedTable' ];
                    $primaryKey = $column[ 'primaryKey' ];
                    // Generate related model name
                    $relatedModelName = Str::studly( Str::ucfirst( $relatedTable ) );
                }

                $fields[] = compact( 'fieldName', 'fieldType', 'isForeignKey', 'relatedTable', 'primaryKey', 'fieldProperties', 'defaultValue', 'nullable', 'unique', 'index' );

                $relationships[] = [
                    'relationName' => Str::lower( Str::singular( $relatedModelName ) ),
                    'fieldName' => $fieldName,
                    'relationType' => $relationType,
                    'relatedModelName' => $relatedModelName,
                    'intermediateModel' => $intermediateModel ?? null,
                    'targetModel' => $targetModel ?? null,
                ];
            } else {
                $fields[] = compact( 'fieldName', 'fieldType', 'isForeignKey', 'fieldProperties', 'defaultValue', 'nullable', 'unique', 'index' );
            }

        }

        // while ( true ) {
        //     $fieldName = $this->ask( 'Enter field name (or type "done" to finish)' );

        //     if ( $fieldName === 'done' ) {
        //         break;
        //     }

        //     $fieldType = $this->choice( 'Select the field type', [ 'bigIncrements', 'bigInteger', 'foreign', 'string', 'text', 'binary', 'boolean', 'char', 'date', 'dateTime', 'decimal', 'double', 'enum', 'float', 'geometry', 'geometryCollection', 'increments', 'integer', 'ipAddress', 'json', 'jsonb', 'lineString', 'longText', 'macAddress', 'mediumIncrements', 'mediumInteger', 'mediumText', 'morphs', 'multiLineString', 'multiPoint', 'multiPolygon', 'nullableMorphs', 'nullableTimestamps', 'point', 'polygon', 'rememberToken', 'set', 'smallIncrements', 'smallInteger', 'softDeletes', 'time', 'timestamp', 'tinyIncrements', 'tinyInteger', 'timestamps', 'uuid' ] );

        //     $fieldProperties = [];

        //     // Set specific properties based on field type
        //     if ( in_array( $fieldType, [ 'string', 'char', 'text' ] ) ) {
        //         $fieldProperties[ 'length' ] = $this->ask( 'Enter the field length', 255 );
        //     } elseif ( $fieldType === 'decimal' ) {
        //         $fieldProperties[ 'precision' ] = $this->ask( 'Enter the precision', 10 );
        //         $fieldProperties[ 'scale' ] = $this->ask( 'Enter the scale', 2 );
        //     } elseif ( $fieldType === 'enum' ) {
        //         $enumValues = $this->ask( 'Enter enum values (comma-separated)' );
        //         $fieldProperties[ 'enumValues' ] = explode( ',', $enumValues );
        //     }

        //     $defaultValue = $this->ask( 'Enter the default value (leave empty for no default)' );

        //     $nullable = $this->confirm( 'Is this field nullable?', false );
        //     $unique = $this->confirm( 'Should this field be unique?', false );
        //     $index = $this->choice( 'Select an index option', [ 'none', 'index', 'unique' ], 'none' );

        //     $hasRelation = false;

        //     $isForeignKey = $fieldType == 'foreign' ? true : false;

        //     if ( $isForeignKey ) {
        //         $hasRelation = true;
        //     } else {
        //         // to morph
        //         $hasRelation = $this->confirm( 'is this field has a relation' );
        //     }

        //     if ( $hasRelation ) {
        //         // generate model relations
        //         $relationType = $this->choice( "Select relation type for field '{$fieldName}'", [ 'belongsTo', 'hasOne', 'hasMany', 'hasManyThrough', 'belongsToMany', 'morphTo', 'morphMany', 'morphToMany', 'morphedByMany' ], 0 );

        //         $relatedTable = '';
        //         $primaryKey = '';

        //         if ( $relationType == 'morphTo' ) {
        //             $relatedModelName = $fieldName;
        //         } elseif ( $relationType == 'hasManyThrough' ) {
        //             $intermediateModel = $this->ask( 'Enter the intermediate model name' );
        //             $targetModel = $this->ask( 'Enter the target model name' );
        //             $relatedModelName = $fieldName;
        //         } else {
        //             $relatedTable = $this->ask( 'Enter the related table name' );
        //             $primaryKey = $this->ask( 'Enter the primary key of the related table', 'id' );
        //             // Generate related model name
        //             $relatedModelName = Str::studly( Str::ucfirst( $relatedTable ) );
        //         }

        //         $fields[] = compact( 'fieldName', 'fieldType', 'isForeignKey', 'relatedTable', 'primaryKey', 'fieldProperties', 'defaultValue', 'nullable', 'unique', 'index' );

        //         $relationships[] = [
        //             'relationName' => Str::lower( Str::singular( $relatedModelName ) ),
        //             'fieldName' => $fieldName,
        //             'relationType' => $relationType,
        //             'relatedModelName' => $relatedModelName,
        //             'intermediateModel' => $intermediateModel ?? null,
        //             'targetModel' => $targetModel ?? null,
        // ];
        //     } else {
        //         $fields[] = compact( 'fieldName', 'fieldType', 'isForeignKey', 'fieldProperties', 'defaultValue', 'nullable', 'unique', 'index' );
        //     }
        // }

        if ( !empty( $fields ) ) {

            $migrationController = new Migration();

            $migrationController->generateMigrationContent( $tableName, $fields );

            $modelController = new Model();

            $modelController->generateModel( $tableName, $fields, $modelName, $relationships );

            // create seeder

            $createSeeder = $this->confirm( 'Do you want to create a seeder for this table?', false );

            if ( $createSeeder ) {
                $numRows = $this->ask( 'Enter the number of rows for the seeder', 10 );

                // Generate the factory for the given table
                $this->generateFactory( $tableName, Str::ucfirst( $tableName ), $fields );

                $this->addHasFactoryTrait( $tableName );

                // Generate the seeder file
                $seederClassName = $this->generateSeederFile( $tableName );

                // Generate the seeder file content
                $seederContent = $this->generateSeederContent( $tableName, $fields, $numRows );

                // Save the seeder content to a file
                $seederFileName = Str::studly( $tableName ) . 'Seeder';
                $seederFilePath = database_path( "seeders/{$seederFileName}.php" );

                file_put_contents( $seederFilePath, $seederContent );

                Artisan::call( 'db:seed' );
            }

            $createController = $this->confirm( 'Do you want to create a resource controller for this table?' );

            if ( $createController ) {
                $this->generateController( $tableName, $fields, $modelName );
            }

            $createApiController = $this->confirm( 'Do you want to create an api controller for this table?' );

            if ( $createApiController ) {
                $this->generateApiController( $tableName, $fields, $modelName );
            }

            $createBladeView = $this->confirm( 'Do you want to create views  for this table?' );

            if ( $createBladeView ) {
                $this->generateBladeViews( $tableName, $fields );
                $this->generateFormFields( $fields, $tableName );
            }

            Artisan::call( 'optimize:clear' );

        } else {
            $this->error( 'No fields provided. Migration creation aborted.' );
        }
    }

    // create factory
    protected function generateFactory( $tableName, $modelClassName, $fields ) {
        // Generate the factory content
        $factoryContent = $this->generateFactoryContent( $tableName, $fields );

        $modelClassName = Str::studly( $tableName );

        // Generate the factory file
        Artisan::call( 'make:factory', [
            'name' => "{$modelClassName}Factory",
            '--model' => "Models\\{$modelClassName}",
        ] );

        $factoryFilePath = database_path( "factories/{$modelClassName}Factory.php" );
        File::put( $factoryFilePath, $factoryContent );

        $this->info( "Factory {$modelClassName}Factory created successfully." );
    }

    protected function generateFactoryContent( $tableName, $fields ) {
        $factoryStub = File::get( __DIR__ . '/stubs/factory.stub' );
        $modelClassName = Str::studly( $tableName );
        $attributes = [];

        foreach ( $fields as $field ) {
            $fakerMethod = $this->getFakerMethodForField( $field[ 'fieldType' ] );

            $foreignFieldTypes = [ 'foreign', 'foreignId', 'unsignedBigInteger', 'foreignUuid', 'foreignUuidNullable' ];

            if ( in_array( $field[ 'fieldType' ], $foreignFieldTypes ) ) {
                $attributes[] = "'{$field['fieldName']}' => \$this->faker->randomElement(\\App\\Models\\" . Str::studly( $field[ 'relatedTable' ] ) . "::pluck('id')),";
            } else {
                $attributes[] = "'{$field['fieldName']}' => \$this->faker->{$fakerMethod}(),";
            }
        }

        return str_replace( [ '{{modelClassName}}', '{{attributes}}' ], [ $modelClassName, implode( '\n', $attributes ) ], $factoryStub );
    }

    protected function addHasFactoryTrait( $tableName ) {
        $modelClassName = Str::studly( $tableName );
        $modelFilePath = app_path( "Models/{$modelClassName}.php" );

        if ( File::exists( $modelFilePath ) ) {
            $modelContent = File::get( $modelFilePath );

            if ( !Str::contains( $modelContent, 'use Illuminate\Database\Eloquent\Factories\HasFactory;' ) ) {
                // Add HasFactory trait to the model
                $updatedModelContent = str_replace( 'use Illuminate\Database\Eloquent\Model;', 'use Illuminate\Database\Eloquent\Model;\nuse Illuminate\Database\Eloquent\Factories\HasFactory;', $modelContent );

                $updatedModelContent = preg_replace( '/(class [\w\s]+ extends Model\s*\{)/', "$1\n    use HasFactory;", $updatedModelContent );

                File::put( $modelFilePath, $updatedModelContent );
                $this->info( "HasFactory trait added to {$modelClassName} model." );
            } else {
                $this->info( "{$modelClassName} model already uses HasFactory trait." );
            }
        }
    }

    protected function getFakerMethodForField( $fieldType ) {
        $fakerMethodMap = [
            'string' => 'word',
            'text' => 'text',
            'integer' => 'randomNumber',
            'smallInteger' => 'randomNumber',
            'bigInteger' => 'randomNumber',
            'unsignedTinyInteger' => 'randomNumber',
            'unsignedSmallInteger' => 'randomNumber',
            'unsignedMediumInteger' => 'randomNumber',
            'unsignedInteger' => 'randomNumber',
            'unsignedBigInteger' => 'randomNumber',
            'float' => 'randomFloat',
            'decimal' => 'randomFloat',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'dateTime',
            'time' => 'time',
            'timestamp' => 'dateTime',
            'uuid' => 'uuid',
            'json' => 'json',
            'jsonb' => 'json',
            'enum' => 'randomElement',
            // Assuming you have an enum field
            'ipAddress' => 'ipv4',
            'macAddress' => 'macAddress',
            'year' => 'year',
            'month' => 'month',
            'day' => 'dayOfMonth',
        ];

        return $fakerMethodMap[ $fieldType ] ?? 'word';
    }

    protected function generateSeederFile( $tableName ) {
        $modelClassName = Str::studly( $tableName );
        $seederClassName = "{$modelClassName}Seeder";

        // Generate the seeder file
        Artisan::call( 'make:seeder', [
            'name' => $seederClassName,
        ] );

        // add seeder to DatabseSeeder file
        $this->addToDatabaseSeeder( $seederClassName );

        return $seederClassName;
    }

    protected function addToDatabaseSeeder( $seederClass ) {
        $databaseSeederPath = database_path( 'seeders/DatabaseSeeder.php' );

        if ( File::exists( $databaseSeederPath ) ) {
            $databaseSeederContent = File::get( $databaseSeederPath );

            // Check if the seeder class is not already present in DatabaseSeeder
            if ( !Str::contains( $databaseSeederContent, $seederClass ) ) {
                // Add the seeder to the run() method in DatabaseSeeder
                $modifiedSeederContent = preg_replace(
                    '/(public function run\(\)(?:\s*:\s*void)?\s*\{)/',
                    "\$1\n        \$this->call({$seederClass}::class);\n        //",
                    $databaseSeederContent,
                    1, // Limit to replace only the first occurrence
                );

                File::put( $databaseSeederPath, $modifiedSeederContent );
                $this->info( "Seeder {$seederClass} added to DatabaseSeeder." );
            } else {
                $this->info( "Seeder {$seederClass} is already present in DatabaseSeeder." );
            }
        }
    }

    protected function generateSeederContent( $tableName, $fields, $numRows ) {
        $seederStub = File::get( __DIR__ . '/stubs/seeder.stub' );
        $modelClassName = Str::studly( $tableName );

        return str_replace( [ '{{tableName}}', '{{modelClassName}}', '{{numRows}}' ], [ Str::studly( $tableName ), $modelClassName, $numRows ], $seederStub );
    }

    protected function generateController( $tableName, $fields, $modelName ) {
        $controllerName = ucfirst( Str::camel( Str::singular( $tableName ) ) ) . 'Controller';

        // Run the make:controller command
        Artisan::call( 'make:controller', [
            'name' => $controllerName,
        ] );

        $controllerContent = $this->generateResourceMethods( $fields, $modelName, $controllerName, $tableName );

        $controllerPath = app_path() . '/Http/Controllers/' . $controllerName . '.php';

        file_put_contents( $controllerPath, $controllerContent );

        $this->generateResoutceRoutes( $tableName, $controllerName );

        $this->info( "Controller {$controllerName} created successfully." );
    }

    protected function generateApiController( $tableName, $fields, $modelName ) {
        $controllerName = ucfirst( Str::camel( Str::singular( $tableName ) ) ) . 'Controller';

        // Run the make:controller command
        Artisan::call( 'make:controller', [
            'name' => 'Api/' . $controllerName,
        ] );

        $controllerContent = $this->generateApiMethods( $fields, $modelName, $controllerName, $tableName );

        $controllerPath = app_path() . '/Http/Controllers/Api/' . $controllerName . '.php';

        file_put_contents( $controllerPath, $controllerContent );

        $this->generateApiRoutes( $tableName, $controllerName );

        $this->info( "Controller Api/{$controllerName} created successfully." );
    }

    protected function generateResourceMethods( $fields, $modelName, $controllerName, $tableName ) {
        // Generate logic for each resource function based on the table fields
        $resourceMethods = [
            'index' => $this->generateIndexMethod( $fields, $modelName, $tableName ),
            'show' => $this->generateShowMethod( $fields, $modelName, $tableName ),
            'create' => $this->generateCreateMethod( $fields, $modelName, $tableName ),
            'store' => $this->generateStoreMethod( $fields, $modelName, $tableName ),
            'edit' => $this->generateEditMethod( $fields, $modelName, $tableName ),
            'update' => $this->generateUpdateMethod( $fields, $modelName, $tableName ),
            'destroy' => $this->generateDestroyMethod( $fields, $modelName, $tableName ),
        ];

        // Get the controller stub content
        $stubContent = File::get( __DIR__ . '/stubs/controller.stub' );

        // Replace placeholders with generated methods
        foreach ( $resourceMethods as $method => $logic ) {
            $stubContent = str_replace( "{{{$method}Method}}", $logic, $stubContent );
        }

        $stubContent = str_replace( [ '{{ControllerName}}', '{{ModelName}}' ], [ $controllerName, $modelName ], $stubContent );

        return $stubContent;
    }

    protected function generateApiMethods( $fields, $modelName, $controllerName, $tableName ) {
        // Generate logic for each resource function based on the table fields
        $apiMethods = [
            'find' => $this->generateApiFindMethod( $fields, $modelName, $tableName ),
            'findAll' => $this->generateApiFindAllMethod( $fields, $modelName, $tableName ),
            'store' => $this->generateApiStorMethod( $fields, $modelName, $tableName ),
            'update' => $this->generateApiUpdateMethod( $fields, $modelName, $tableName ),
            'delete' => $this->generateApiDeleteMethod( $fields, $modelName, $tableName ),
        ];

        // Get the controller stub content
        $stubContent = File::get( __DIR__ . '/stubs/apiController.stub' );

        // Replace placeholders with generated methods
        foreach ( $apiMethods as $method => $logic ) {
            $stubContent = str_replace( "{{{$method}}}", $logic, $stubContent );
        }

        $stubContent = str_replace( [ '{{ControllerName}}', '{{ModelName}}' ], [ $controllerName, $modelName ], $stubContent );

        return $stubContent;
    }

    protected function generateIndexMethod( $fields, $modelName, $tableName ) {

        $indexStub = File::get( __DIR__ . '/stubs/methods/resources/index.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $indexStub );

    }

    protected function generateApiFindMethod( $fields, $modelName, $tableName ) {

        $findStub = File::get( __DIR__ . '/stubs/methods/api/find.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $findStub );

    }

    protected function generateApiFindAllMethod( $fields, $modelName, $tableName ) {

        $findAllStub = File::get( __DIR__ . '/stubs/methods/api/findAll.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $findAllStub );

    }

    protected function generateShowMethod( $fields, $modelName, $tableName ) {

        $showStub = File::get( __DIR__ . '/stubs/methods/resources/show.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $showStub );

    }

    protected function generateCreateMethod( $fields, $modelName, $tableName ) {

        $createStub = File::get( __DIR__ . '/stubs/methods/resources/create.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $createStub );

    }

    protected function generateEditMethod( $fields, $modelName, $tableName ) {

        $editStub = File::get( __DIR__ . '/stubs/methods/resources/edit.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $editStub );

    }

    protected function generateStoreMethod( $fields, $modelName, $tableName ) {

        $storeLogic = '';

        $validationRules = [];

        foreach ( $fields as $field ) {
            $fieldType = $field[ 'fieldType' ];
            $isNullabe = $field[ 'nullable' ];
            $fieldName = $field[ 'fieldName' ];
            $rules = $this->generateValidationRulesForFieldType( $fieldType, $isNullabe );
            $validationRules[ $fieldName ] = $rules;
        }

        // Generate validation logic
        $validationLogic = '';
        foreach ( $validationRules as $field => $rules ) {
            $rulesString = implode( '|', $rules );
            $validationLogic .= "\n    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $storeLogic .= "  '{$fieldName}' => \$request->{$fieldName},\n";
        }

        $storeStub = File::get( __DIR__ . '/stubs/methods/resources/store.stub' );

        return str_replace( [ '{{ModelName}}', '{{StoreLogic}}', '{{tableName}}', '{{validationLogic}}' ], [ $modelName, $storeLogic, $tableName, $validationLogic ], $storeStub );

    }

    protected function generateApiStorMethod( $fields, $modelName, $tableName ) {

        $storeLogic = '';

        $validationRules = [];

        foreach ( $fields as $field ) {
            $fieldType = $field[ 'fieldType' ];
            $isNullabe = $field[ 'nullable' ];
            $fieldName = $field[ 'fieldName' ];
            $rules = $this->generateValidationRulesForFieldType( $fieldType, $isNullabe );
            $validationRules[ $fieldName ] = $rules;
        }

        // Generate validation logic
        $validationLogic = '';
        foreach ( $validationRules as $field => $rules ) {
            $rulesString = implode( '|', $rules );
            $validationLogic .= "\n    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $storeLogic .= "  '{$fieldName}' => \$request->{$fieldName},\n";
        }

        $storeStub = File::get( __DIR__ . '/stubs/methods/api/store.stub' );

        return str_replace( [ '{{ModelName}}', '{{StoreLogic}}', '{{tableName}}', '{{validationLogic}}' ], [ $modelName, $storeLogic, $tableName, $validationLogic ], $storeStub );

    }

    protected function generateUpdateMethod( $fields, $modelName, $tableName ) {

        $updateLogic = '';

        $validationRules = [];

        foreach ( $fields as $field ) {
            $fieldType = $field[ 'fieldType' ];
            $isNullabe = $field[ 'nullable' ];
            $fieldName = $field[ 'fieldName' ];
            $rules = $this->generateValidationRulesForFieldType( $fieldType, $isNullabe );
            $validationRules[ $fieldName ] = $rules;
        }

        // Generate validation logic
        $validationLogic = '';
        foreach ( $validationRules as $field => $rules ) {
            $rulesString = implode( '|', $rules );
            $validationLogic .= "\n    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $updateLogic .= "  '{$fieldName}' => \$request->{$fieldName},\n";
        }

        $updateStub = File::get( __DIR__ . '/stubs/methods/resources/update.stub' );

        return str_replace( [ '{{ModelName}}', '{{UpdateLogic}}', '{{tableName}}', '{{varName}}', '{{validationLogic}}' ], [ $modelName, $updateLogic, $tableName, Str::singular( $tableName ), $validationLogic ], $updateStub );

    }

    protected function generateApiUpdateMethod( $fields, $modelName, $tableName ) {

        $updateLogic = '';

        $validationRules = [];

        foreach ( $fields as $field ) {
            $fieldType = $field[ 'fieldType' ];
            $isNullabe = $field[ 'nullable' ];
            $fieldName = $field[ 'fieldName' ];
            $rules = $this->generateValidationRulesForFieldType( $fieldType, $isNullabe );
            $validationRules[ $fieldName ] = $rules;
        }

        // Generate validation logic
        $validationLogic = '';
        foreach ( $validationRules as $field => $rules ) {
            $rulesString = implode( '|', $rules );
            $validationLogic .= "\n    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $updateLogic .= "  '{$fieldName}' => \$request->{$fieldName},\n";
        }

        $updateStub = File::get( __DIR__ . '/stubs/methods/api/update.stub' );

        return str_replace( [ '{{ModelName}}', '{{UpdateLogic}}', '{{tableName}}', '{{varName}}', '{{validationLogic}}' ], [ $modelName, $updateLogic, $tableName, Str::singular( $tableName ), $validationLogic ], $updateStub );

    }

    protected function generateDestroyMethod( $fields, $modelName, $tableName ) {

        $destroyStub = File::get( __DIR__ . '/stubs/methods/resources/destroy.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}', '{{varName}}' ], [ $modelName, $tableName, Str::singular( $tableName ) ], $destroyStub );

    }

    protected function generateApiDeleteMethod( $fields, $modelName, $tableName ) {

        $deleteStub = File::get( __DIR__ . '/stubs/methods/api/delete.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}', '{{varName}}' ], [ $modelName, $tableName, Str::singular( $tableName ) ], $deleteStub );

    }

    protected function generateValidationRulesForFieldType( $fieldType, $nullable ) {
        $rules = [];

        if ( !$nullable ) {
            $rules[] = 'required';
        }

        switch ( $fieldType ) {
            case 'string':
            $rules[] = 'string|max:255';
            break;
            case 'integer':
            $rules[] = 'integer';
            break;
            case 'boolean':
            $rules[] = 'boolean';
            break;
            case 'date':
            $rules[] = 'date';
            break;
            case 'time':
            $rules[] = 'time';
            break;
            case 'datetime':
            $rules[] = 'date_time';
            break;
            case 'email':
            $rules[] = 'email';
            break;
            case 'numeric':
            $rules[] = 'numeric';
            break;
            case 'url':
            $rules[] = 'url';
            break;
            case 'file':
            $rules[] = 'file';
            break;
            case 'image':
            $rules[] = 'image';
            break;
            // Add more cases for other field types

            default:
            // Handle unsupported field types
            break;
        }

        return $rules;
    }

    protected function generateApiRoutes( $tableName, $controllerName ) {
        $routesFilePath = base_path( 'routes/api.php' );

        $apiContent = File::get( base_path( 'routes/api.php' ) );

        $importStatement = "<?php \n use App\Http\Controllers\Api\\$controllerName; \n";

        $routeApiContent = str_replace( [ '<?php' ], [ $importStatement ], $apiContent );

        File::put( $routesFilePath, $routeApiContent );

        $apiRouteStub = File::get( __DIR__ . '/stubs/routes/api.stub' );

        $routeContent = str_replace( [ '{{controllerName}}', '{{tableName}}' ], [ $controllerName, $tableName ], $apiRouteStub );

        // Append the generated route content to api.php
        File::append( $routesFilePath, $routeContent );

    }

    protected function generateResoutceRoutes( $tableName, $controllerName ) {
        $routesFilePath = base_path( 'routes/web.php' );

        $routeontent = File::get( base_path( 'routes/web.php' ) );

        $importStatement = "<?php \n use App\Http\Controllers\\$controllerName; \n";
        $routeApiContent = str_replace( [ '<?php' ], [ $importStatement ], $routeontent );
        File::put( $routesFilePath, $routeApiContent );

        $resourceRouteStub = File::get( __DIR__ . '/stubs/routes/web.stub' );
        $routeContent = str_replace( [ '{{controllerName}}', '{{tableName}}' ], [ $controllerName, $tableName ], $resourceRouteStub );
        File::append( $routesFilePath, $routeContent );
    }

    protected function generateBladeViews( $tableName, $fields ) {

        $layoutPath = resource_path( 'views/layouts' );

        if ( !File::exists( $layoutPath ) ) {
            File::makeDirectory( $layoutPath );
        }

        $dashboardPath = resource_path( 'views/layouts/dashboard.blade.php' );

        if ( !File::exists( $dashboardPath ) ) {
            $dashboardStub = File::get( __DIR__ . '/stubs/layouts/dashboard.stub' );

            $menu = File::get( __DIR__ . '/stubs/component/menu.stub' );
            $menuContent = str_replace( [ '{{tableName}}' ], [ $tableName ], $menu );

            $menuContent = str_replace( [ '{{sidebarItems}}' ], [ $menuContent ], $dashboardStub );

            File::put( $dashboardPath, $menuContent );
        } else {

            $dashboardContent = File::get( resource_path( 'views/layouts/dashboard.blade.php' ) );

            // Step 2: Modify the HTML Content
            $menu = File::get( __DIR__ . '/stubs/component/menu.stub' );

            $menuContent = str_replace( [ '{{tableName}}' ], [ $tableName ], $menu );

            $dom = new \DOMDocument();

            $dom->loadHTML( $dashboardContent, LIBXML_NOERROR );

            $divId = 'nav-menu';
            // Replace with your actual div ID
            $specificDiv = $dom->getElementById( $divId );

            if ( $specificDiv ) {
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML( $menuContent );
                $specificDiv->appendChild( $fragment );
            }

            $modifiedHtmlContent = $dom->saveHTML();

            $modifiedHtmlContent = str_replace( [ '%7B%7B', '%7D%7D', '%20' ], [ '{{', '}}', '' ], $modifiedHtmlContent );

            // Step 3: Write Modified Content
            File::put( $dashboardPath, $modifiedHtmlContent );
        }

        $folderPath = resource_path( 'views/pages' );

        if ( !File::exists( $folderPath ) ) {
            File::makeDirectory( $folderPath );
        }

        // Create the directory for the views if it doesn't exist
        $viewsDirectory = resource_path("views/pages/{$tableName}");
        if (!File::exists($viewsDirectory)) {
            File::makeDirectory($viewsDirectory);
        }

        // Generate the index.blade.php file

        $indexViewStub = File::get(__DIR__ . '/stubs/views/index.stub');

        $headers = $this->generateTableHeaders($fields);
        $records = $this->generateTableColumns($fields);

        $indexContent = str_replace([' {
        {
            tableName}
        }
        ', ' {
            {
                headers}
            }
            ', ' {
                {
                    records}
                }
                '], [$tableName, $headers, $records], $indexViewStub);

        File::put("{$viewsDirectory}/index.blade.php", $indexContent);

        // Generate the create.blade.php file
        $createView = <<<EOT
        <!-- contents of create.blade.php -->
        EOT;
        File::put("{$viewsDirectory}/create.blade.php", $createView);

        // Generate the edit.blade.php file
        $editView = <<<EOT
        <!-- contents of edit.blade.php -->
        EOT;
        File::put("{$viewsDirectory}/edit.blade.php", $editView);

        // Generate the show.blade.php file
        $showView = <<<EOT
        <!-- contents of show.blade.php -->
        EOT;
        File::put("{$viewsDirectory}/show.blade.php", $showView);



        $showViewStub = File::get(__DIR__ . '/stubs/views/show.stub');

        $data = $this->generateFieldShow($fields);

        $showContent = str_replace([' {
                    {
                        tableName}
                    }
                    ', ' {
                        {
                            headers}
                        }
                        ', ' {
                            {
                                fields}
                            }
                            '], [$tableName, $headers, $data], $showViewStub);

        File::put("{$viewsDirectory}/show.blade.php", $showContent);



        $this->info('Blade views generated successfully.');
    }


    protected function generateFieldShow($fields)
    {
        $headers = '';
        foreach ($fields as $field) {
            $headers .= "<h4>{{\$record->" . $field['fieldName'] . "}}</h4>\n     ";
        }
        return $headers;
    }


    protected function generateTableHeaders($fields)
    {
        $headers = '';
        foreach ($fields as $field) {
            $headers .= "<th>{$field['fieldName']}</th>\n        ";
        }
        return $headers;
    }

    protected function generateTableColumns($fields)
    {
        $columns = '';
        foreach ($fields as $field) {
            $columns .= "<td>{{ \$record->{$field['fieldName']} }}</td>\n                    ";
        }
        return $columns;
    }

    protected function generateFormFields($fields, $tableName)
    {
        $formFields = '';
        foreach ($fields as $field) {
            $formFields .= $this->generateFormField($field);
        }

        $createStub = File::get(__DIR__ . '/stubs/views/create.stub');
        $createView = str_replace([' {
                                {
                                    tableName}
                                }
                                ', ' {
                                    {
                                        formFields}
                                    }
                                    '], [$tableName, $formFields], $createStub);


        $updateStub = File::get(__DIR__ . '/stubs/views/edit.stub');
        $updateView = str_replace([' {
                                        {
                                            tableName}
                                        }
                                        ', ' {
                                            {
                                                formFields}
                                            }
                                            ', ' {
                                                {
                                                    varName}
                                                }
                                                '], [$tableName, $formFields, Str::singular($tableName)], $updateStub);


        $viewsDirectory = resource_path("views/pages/{$tableName}");
        File::put("{$viewsDirectory}/create.blade.php", $createView);
        File::put("{$viewsDirectory}/edit.blade.php", $updateView);
    }

    protected function generateFormField($field)
    {

        $foreignFieldTypes = ['foreign', 'foreignId', 'unsignedBigInteger', 'foreignUuid', 'foreignUuidNullable'];

        if (in_array($field['fieldType'], $foreignFieldTypes)) {
            return $this->generateSelectField($field);
        } else {
            switch ($field['fieldType']) {
                case 'date':
                    return $this->generateDateField($field);
                case 'datetime':
                    return $this->generateDatetimeField($field);
                case 'text':
                case 'longtext':
                    return $this->generateTextareaField($field);
                case 'integer':
                case 'bigint':
                case 'smallint':
                case 'float':
                case 'double':
                    return $this->generateNumberField($field);
                case 'enum':
                    return $this->generateEnumField($field);
                case 'boolean':
                    return $this->generateBooleanField($field);
                default:
                    return $this->generateTextField($field);
            }
        }

    }

    protected function generateDateField($field)
    {

        $dateStub = File::get(__DIR__ . '/stubs/form/date.stub');
        $dateField = str_replace([' {
                                                    {
                                                        $fieldName}
                                                    }
                                                    '], [$field['fieldName']], $dateStub);


        return $dateField;

    }

    protected function generateDatetimeField($field)
    {

        $dateStub = File::get(__DIR__ . '/stubs/form/dateTime.stub');
        $dateField = str_replace([' {
                                                        {
                                                            $fieldName}
                                                        }
                                                        '], [$field['fieldName']], $dateStub);

        return $dateField;

    }

    protected function generateTextareaField($field)
    {
        $textStub = File::get(__DIR__ . '/stubs/form/textArea.stub');
        $textField = str_replace([' {
                                                            {
                                                                $fieldName}
                                                            }
                                                            '], [$field['fieldName']], $textStub);

        return $textField;
    }

    protected function generateNumberField($field)
    {
        $numberStub = File::get(__DIR__ . '/stubs/form/number.stub');
        $numberField = str_replace([' {
                                                                {
                                                                    $fieldName}
                                                                }
                                                                '], [$field['fieldName']], $numberStub);

        return $numberField;
    }

    protected function generateEnumField($field)
    {
        $enumValues = $field['fieldProperties']['enum_values'];

        $options = "";
        foreach ($enumValues as $value) {
            $options .= "<option value=\"$value\">{{ \$value }}</option>";
        }
        $enumStub = File::get(__DIR__ . '/stubs/form/enum.stub');
        $enumField = str_replace([' {
                                                                    {
                                                                        $fieldName}
                                                                    }
                                                                    ', ' {
                                                                        {
                                                                            options}
                                                                        }
                                                                        '], [$field['fieldName'], $options], $enumStub);

        return $enumField;

    }

    protected function generateSelectField($field)
    {
        $relatedModel = '\\App\\Models\\' . Str::studly(Str::ucfirst($field['relatedTable']));
        $selectOptions = "$relatedModel::pluck('id')";

        $selectStub = File::get(__DIR__ . '/stubs/form/select.stub');
        $selectField = str_replace([' {
                                                                            {
                                                                                $fieldName}
                                                                            }
                                                                            ', ' {
                                                                                {
                                                                                    selectOptions}
                                                                                }
                                                                                '], [$field['fieldName'], $selectOptions], $selectStub);

        return $selectField;

    }

    protected function generateTextField($field)
    {
        $textStub = File::get(__DIR__ . '/stubs/form/text.stub');
        $textField = str_replace([' {
                                                                                    {
                                                                                        $fieldName}
                                                                                    }
                                                                                    '], [$field['fieldName']], $textStub);

        return $textField;
    }

    protected function generateBooleanField($field)
    {
        $radioStub = File::get(__DIR__ . '/stubs/form/radio.stub');
        $radioField = str_replace([' {
                                                                                        {
                                                                                            $fieldName}
                                                                                        }
                                                                                        '], [$field['fieldName' ] ], $radioStub );

                                                                                        return $radioField;
                                                                                    }
                                                                                }
