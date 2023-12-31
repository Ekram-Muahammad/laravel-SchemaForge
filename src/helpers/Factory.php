<?php
namespace Ekram\SchemaForge\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Factory {

    // create factory

    public function generateFactory( $tableName, $modelClassName, $fields ) {
        // Generate the factory content
        $factoryContent = $this->generateFactoryContent( $tableName, $fields );

        $modelClassName = Str::ucfirst( $tableName );

        // Generate the factory file
        Artisan::call( 'make:factory', [
            'name' => "{$modelClassName}Factory",
            '--model' => "Models\\{$modelClassName}",
        ] );

        $factoryFilePath = database_path( "factories/{$modelClassName}Factory.php" );
        file_put_contents( $factoryFilePath, $factoryContent );
    }

    public function generateFactoryContent( $tableName, $fields ) {
        $factoryStub = file_get_contents( __DIR__ . '/stubs/factory.stub' );
        $modelClassName = Str::studly( $tableName );
        $attributes = [];

        foreach ( $fields as $field ) {
            $fakerMethod = $this->getFakerMethodForField( $field[ 'fieldType' ] );

            $foreignFieldTypes = [ 'foreign', 'foreignId', 'unsignedBigInteger', 'foreignUuid', 'foreignUuidNullable' ];

            if ( in_array( $field[ 'fieldType' ], $foreignFieldTypes ) ) {
                $attributes[] = "'{$field['fieldName']}' => \$this->faker->randomElement(\\App\\Models\\" . Str::studly( $field[ 'relatedTable' ] ) . "::pluck('id')),";
            } else if ( $field[ 'fieldType' ] == 'enum' ) {
                $attributes[] = "'{$field['fieldName']}' => \$this->faker->randomElement(['".implode("','",$field['fieldProperties']['enumValues'])."']),";
            } else {
                $attributes[] = "'{$field['fieldName']}' => \$this->faker->{$fakerMethod}(),";
            }
        }

        return preg_replace( [ '/{{\s*modelClassName\s*}}/', '/{{\s*attributes\s*}}/' ], [ $modelClassName, implode( PHP_EOL, $attributes ) ], $factoryStub );
    }

    public function addHasFactoryTrait( $tableName ) {
        $modelClassName = Str::studly( $tableName );
        $modelFilePath = app_path( "Models/{$modelClassName}.php" );

        if ( file_exists( $modelFilePath ) ) {
            $modelContent = file_get_contents( $modelFilePath );

            if ( !Str::contains( $modelContent, 'use Illuminate\Database\Eloquent\Factories\HasFactory;' ) ) {
                // Add HasFactory trait to the model
                $updatedModelContent = str_replace( 'use Illuminate\Database\Eloquent\Model;', 'use Illuminate\Database\Eloquent\Model;'.PHP_EOL.'use Illuminate\Database\Eloquent\Factories\HasFactory;', $modelContent );

                $updatedModelContent = preg_replace( '/(class [\w\s]+ extends Model\s*\{)/', "$1".PHP_EOL."    use HasFactory;", $updatedModelContent );

                file_put_contents( $modelFilePath, $updatedModelContent );
            } else {
                //    Artisan::info( "{$modelClassName} model already uses HasFactory trait." );
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
            'ipAddress' => 'ipv4',
            'macAddress' => 'macAddress',
            'year' => 'year',
            'month' => 'month',
            'day' => 'dayOfMonth',
        ];

        return $fakerMethodMap[ $fieldType ] ?? 'word';
    }
}