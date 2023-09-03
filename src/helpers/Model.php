<?php
namespace Ekram\SchemaForge\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Model {

    public function generateModel( $tableName, $fields, $modelName, $relationships ) {
        // Generate Eloquent model
        $modelTemplate = file_get_contents( __DIR__ . '/stubs/model.stub' );

        $fillableFields = implode( "', '", array_column( $fields, 'fieldName' ) );
        $relationFunctions = '';

        foreach ( $relationships as $relationship ) {
            if ( $relationship[ 'relationType' ] === 'morphTo' ) {
                $relationFunction = PHP_EOL."    public function {$relationship['fieldName']}()".PHP_EOL."    {".PHP_EOL;
                $relationFunction .= "        return \$this->{$relationship['relationType']}();".PHP_EOL;
                $relationFunction .= '    }'.PHP_EOL;
                $relationFunctions .= $relationFunction;
            } elseif ( $relationship[ 'relationType' ] === 'hasManyThrough' ) {
                $intermediateModelName = Str::studly( $relationship[ 'intermediateModel' ] );
                $targetModelName = Str::studly( $relationship[ 'targetModel' ] );
                $relationFunction = PHP_EOL."    public function {$relationship['relationName']}()".PHP_EOL."    {".PHP_EOL;
                $relationFunction .= "        return \$this->{$relationship['relationType']}({$targetModelName}::class, {$intermediateModelName}::class);".PHP_EOL;
                $relationFunction .= '    }'.PHP_EOL;
                $relationFunctions .= $relationFunction;
            } else {
                $relationFunction = PHP_EOL."    public function {$relationship['relationName']}()".PHP_EOL."    {".PHP_EOL;
                $relationFunction .= "        return \$this->{$relationship['relationType']}({$relationship['relatedModelName']}::class, '{$relationship['fieldName']}');".PHP_EOL;
                $relationFunction .= '    }'.PHP_EOL;
                $relationFunctions .= $relationFunction;
            }
        }

        Artisan::call( 'make:model', [
            'name' => $modelName,
        ] );

        $modelContent = preg_replace( [ '/{{\s*modelName\s*}}/', '/{{\s*tableName\s*}}/', '/{{\s*fillable\s*}}/', '/{{\s*relations\s*}}/' ], [ $modelName, Str::lower( $tableName ), $fillableFields, $relationFunctions ], $modelTemplate );

        $modelPath = app_path() . '/Models/' . $modelName . '.php';

        file_put_contents( $modelPath, $modelContent );

    }
}