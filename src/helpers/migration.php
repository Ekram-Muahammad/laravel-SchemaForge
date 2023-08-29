<?php
namespace Ekram\ArtisanCrud\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Migration {

    public function generateMigrationContent( $tableName, $fields ) {
        $fieldsContent = '';

        foreach ( $fields as $field ) {
            if ( $field[ 'isForeignKey' ] ) {
                $fieldsContent .= sprintf( "\$table->foreignId('%s')->constrained('%s', '%s')", $field[ 'fieldName' ],
                $field[ 'relatedTable' ], $field[ 'primaryKey' ] );
            } else {
                $fieldsContent .= sprintf( "\$table->%s('%s')", $field[ 'fieldType' ], $field[ 'fieldName' ] );
            }

            if ( isset( $field[ 'fieldProperties' ] ) ) {
                foreach ( $field[ 'fieldProperties' ] as $property => $value ) {
                    $fieldsContent .= "->{$property}({$value})";
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

            $fieldsContent .= ';\n';
        }

        $tb = Str::ucfirst( $tableName );

        $migrationContent = str_replace( [ '{{migrateName}}', '{{tableName}}', '{{fields}}' ], [ $tb,

        $tb, $fieldsContent ], file_get_contents( __DIR__ . '/stubs/migration.stub' ) );

        $migrationName = date( 'Y_m_d_His' ) . '_create_' . $tableName . '_table';

        file_put_contents( database_path( 'migrations' ) . '/' . $migrationName . '.php', $migrationContent );

        Artisan::call( 'migrate' );

    }
}