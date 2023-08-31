<?php
namespace Ekram\ArtisanCrud\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Field {
    public function generateFields( $columns ) {

        $fields = [];
        $relationships = [];

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

        return [
            'fields'=>$fields,
            'relationships'=>$relationships
        ];

    }
}