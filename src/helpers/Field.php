<?php
namespace Ekram\SchemaForge\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class Field {
    public function generateFields( $columns ) {

        $fields = [];
        $relationships = [];

        foreach ( $columns as $column ) {

            $fieldProperties = [];
            $fieldName = $column[ 'name' ];
            $fieldType = $column[ 'type' ] ?? "string";
            $defaultValue = $column[ 'defaultValue' ] ?? "";
            $nullable = $column[ 'nullable' ] ?? false;
            $unique = $column[ 'unique' ] ?? false;
            $index = $column[ 'index' ] ?? "none";

            if ( in_array( $fieldType, [ 'string', 'char', 'text' ] ) ) {
                $fieldProperties[ 'length' ] = $column[ 'length' ] ?? 255;
            } elseif ( $fieldType === 'decimal' ) {
                $fieldProperties[ 'precision' ] = $column[ 'length' ] ?? 8;
                $fieldProperties[ 'scale' ] = $column[ 'precision' ] ?? 2;
            } elseif ( $fieldType === 'enum' ) {
                $enumValues = is_array($column[ 'enum_values' ]) ? $column[ 'enum_values' ] : explode(",",$column[ 'enum_values' ]);
                $fieldProperties[ 'enumValues' ] = $enumValues;
            }

            $hasRelation = false;

            $isForeignKey = $fieldType == 'foreign' ? true : false;

            if ( $isForeignKey ) {
                $hasRelation = true;
            } else {
                // to morph
                if ( isset( $column[ 'hasRelation' ] ) ) {
                    $hasRelation = $column[ 'hasRelation' ];
                } else {
                    $hasRelation = false;
                }

            }

            if ( $hasRelation && isset( $column[ 'relation' ] ) ) {
                // generate model relations
                $relationType = $column[ 'relation' ][ 'relationType' ] ?? "belongsTo";

                $relatedTable = '';
                $relatedColumn = '';

                if ( $relationType == 'morphTo' ) {
                    $relatedModelName = $fieldName;
                } elseif ( $relationType == 'hasManyThrough' ) {
                    $intermediateModel = $column[ 'relation' ][ 'intermediateModel' ] ?? "";
                    $targetModel = $column[ 'relation' ][ 'targetModel' ] ?? "";
                    $relatedModelName = $fieldName;
                } else {
                    $relatedTable = $column[ 'relation' ][ 'relatedTable' ] ?? "";
                    $relatedColumn = $column[ 'relation' ][ 'relatedColumn' ] ?? "";
                    // Generate related model name
                    $relatedModelName = Str::studly( Str::ucfirst( $relatedTable ) );
                }

                $fields[] = compact( 'fieldName', 'fieldType', 'isForeignKey', 'relatedTable', 'relatedColumn', 'fieldProperties', 'defaultValue', 'nullable', 'unique', 'index' );

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