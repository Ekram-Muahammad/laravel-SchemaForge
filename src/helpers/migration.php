<?php
namespace Ekram\SchemaForge\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Migration {

    public function generateMigrationContent( $tableName, $fields ) {
        $fieldsContent = '';

        foreach ( $fields as $field ) {
            if ( $field[ 'isForeignKey' ] ) {
                $fieldsContent .= sprintf( "\$table->foreignId('%s')->constrained('%s', '%s')", $field[ 'fieldName' ],
                $field[ 'relatedTable' ], $field[ 'relatedColumn' ] );
            } else if ( $field[ 'fieldType' ] == 'enum' ) {
                $enumValues=implode('\',\'',$field['fieldProperties']['enumValues']);
                $fieldsContent .= sprintf( "\$table->enum('%s',['%s'])", $field[ 'fieldName' ],$enumValues );
            } else {
                $fieldsContent .= sprintf( "\$table->%s('%s')", $field[ 'fieldType' ], $field[ 'fieldName' ] );
            }

            if ( isset( $field[ 'fieldProperties' ] ) ) {
                foreach ( $field[ 'fieldProperties' ] as $property => $value ) {
                    if ( $property != 'enumValues' ) {
                        $fieldsContent .= "->{$property}({$value})";
                    }
                }
            }

            if ( isset( $field[ 'defaultValue' ] ) && $field[ 'defaultValue' ] !== '' ) {
                $fieldsContent .= "->default('{$field['defaultValue']}')";
            }

            if ( $field[ 'nullable' ] ) {
                $fieldsContent .= '->nullable()';
            }

            if ( $field[ 'unique' ] ) {
                $fieldsContent .= '->unique()';
            }

            if ( $field[ 'index' ] === 'index' ) {
                $fieldsContent .= '->index()';
            } elseif ( $field[ 'index' ] === 'unique' ) {
                $fieldsContent .= '->unique()';
            }

            $fieldsContent .= ';'. PHP_EOL;
        }

        $tb = Str::ucfirst( $tableName );

        $migrationStubContent = file_get_contents( __DIR__ . '/stubs/migration.stub' );

        $migrationContent = preg_replace( [ '/{{\s*migrateName\s*}}/', '/{{\s*tableName\s*}}/', '/{{\s*fields\s*}}/' ], [ $tb,

        Str::lower( $tableName ), $fieldsContent ], $migrationStubContent );

        $migrationName = date( 'Y_m_d_His' ) . '_create_' . Str::studly($tableName) . '_table';

        file_put_contents( database_path( 'migrations' ) . '/' . $migrationName . '.php', $migrationContent );
        try {
            Artisan::call( 'migrate' );
        } catch ( \Throwable $th ) {
            //throw $th;
        }

    }
}