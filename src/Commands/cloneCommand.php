<?php
namespace Ekram\SchemaForge\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CloneCommand extends Command {
    protected $signature = 'db:clone';

    protected $description = 'Convert Database schema to json';

    protected $typeMapping = [
        // Numeric Types
        'int' => 'integer',
        'smallint' => 'smallInteger',
        'mediumint' => 'mediumInteger',
        'bigint' => 'bigInteger',
        'tinyint' => 'tinyInteger',
        'decimal' => 'decimal',
        'numeric' => 'decimal',
        'float' => 'float',
        'double' => 'double',

        // String Types
        'char' => 'string',
        'varchar' => 'string',
        'text' => 'text',
        'mediumtext' => 'mediumText',
        'longtext' => 'longText',

        // Date and Time Types
        'date' => 'date',
        'datetime' => 'dateTime',
        'timestamp' => 'timestamp',
        'time' => 'time',
        'year' => 'year',

        // Binary Data Types
        'binary' => 'binary',
        'varbinary' => 'binary',
        'blob' => 'binary',
        'tinyblob' => 'binary',
        'mediumblob' => 'binary',
        'longblob' => 'binary',

        // Boolean Type
        'boolean' => 'boolean',

        // Enum Type
        'enum' => 'enum',

        // JSON Types
        'json' => 'json',
        'jsonb' => 'jsonb',

        // UUID Type
        'uuid' => 'uuid',

        // Geometric Types ( Spatial )
        'point' => 'point',
        'linestring' => 'lineString',
        'polygon' => 'polygon',
        'geometry' => 'geometry',
        'geometrycollection' => 'geometryCollection',
        'multipoint' => 'multiPoint',
        'multilinestring' => 'multiLineString',
        'multipolygon' => 'multiPolygon',
        'multigeometry' => 'multiGeometry',
    ];

    public function handle() {
        $tables = DB::select( 'SHOW TABLES' );

        $table = [];

        foreach ( $tables as $tb ) {
            $tableName = reset( $tb );
            $table[ 'tableName' ] =  $tableName;
            $table[ 'migration' ] = true;
            $table[ 'seeder' ] = true;
            $table[ 'seederNumRows' ] = 10;
            $table[ 'resourceController' ] = true;
            $table[ 'apiController' ] = true;
            $table[ 'views' ] = true;

            $table[ 'columns' ] = [];
            $field = [];

            // Get the columns for each table
            $columns = DB::select( "DESCRIBE {$table[ 'tableName' ]}" );

            foreach ( $columns as $column ) {
                $columnName = $column->Field;

                if (in_array( $columnName, [ 'id', 'created_at', 'updated_at' ] ) ) {
                    continue;
                }

                $field[ 'name' ] = $column->Field;

                $databaseColumnType =  $column->Type;

                $field[ 'type' ] = 'string';
                // Default to string
                foreach ( $this->typeMapping as $dbType => $migrationType ) {
                    if ( strpos( $databaseColumnType, $dbType ) !== false ) {
                        $field[ 'type' ]  = $migrationType;

                        try {
                            if ( preg_match( '/\((\d+)\)/', $databaseColumnType, $matches ) ) {
                                $field[ 'length' ] = $matches[ 1 ];
                            }
                        } catch ( \Throwable $th ) {
                            //throw $th;
                        }

                        try {
                            if ( $dbType === 'decimal' || $dbType === 'numeric' ) {
                                if ( preg_match( '/\((\d+),(\d+)\)/', $databaseColumnType, $matches ) ) {
                                    $field[ 'precision' ] = $matches[ 1 ];
                                    $field[ 'scale' ] = $matches[ 2 ];
                                }
                            }
                        } catch ( \Throwable $th ) {
                            //throw $th;
                        }

                        try {
                            if ( $dbType === 'enum' ) {
                                // Extract enum values from the column type definition
                                if ( preg_match( '/\((.*?)\)/', $databaseColumnType, $matches ) ) {
                                    $enumValues = explode( ',', $matches[ 1 ] );
                                    // Remove surrounding single quotes and trim whitespace from enum values
                                    $enumValues = array_map( function ( $value ) {
                                        return trim( $value, "'" );
                                    }
                                    , $enumValues );

                                    $field[ 'values' ] = $enumValues;
                                }
                            }
                        } catch ( \Throwable $th ) {
                            //throw $th;
                        }

                        break;
                    }
                }

                $field[ 'nullable' ] = $column->Null == 'YES' ? true : false;
                ;
                $field[ 'unique' ]  = $column->Key === 'UNI' ? true : false;
                $field[ 'defaultValue' ] = $column->Default ?? '';

                $field[ 'index' ] = '';
                if ( $column->Key === 'PRI' ) {
                    $field[ 'index' ] = 'Primary';
                } elseif ( $column->Key === 'UNI' ) {
                    $field[ 'index' ] = 'Unique';
                } elseif ( $column->Key === 'MUL' ) {
                    $field[ 'index' ] = 'Index';
                }

                $field[ 'hasRelation' ] = $column->Key === 'MUL' ? true :false;

                $relatedTable = '';
                $relatedColumn = '';
                if ( $field[ 'hasRelation' ] ) {
                    $constraintInfo = DB::select( "
                            SELECT
                                TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                            FROM
                                information_schema.KEY_COLUMN_USAGE
                            WHERE
                                TABLE_NAME = '$tableName' AND COLUMN_NAME = '$columnName'
                        " );
                    if ( !empty( $constraintInfo ) ) {
                        $column[ 'relatedTable' ] = $constraintInfo[ 0 ]->REFERENCED_TABLE_NAME;
                        $column[ 'relatedColumn' ] = $constraintInfo[ 0 ]->REFERENCED_COLUMN_NAME;
                    }
                }
                $table[ 'columns' ][] = $field;

            }

            $crudPath = base_path() . '/cruds/';

            if ( !file_exists( $crudPath ) ) {
                File::makeDirectory( $crudPath );

            }

            $filePath = $crudPath.$tableName.'.json';

            file_put_contents( $filePath, json_encode( $table ) );
        }
    }
}