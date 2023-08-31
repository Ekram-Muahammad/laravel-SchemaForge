<?php
namespace Ekram\ArtisanCrud\Helpers;

class Validation {

    public function generateValidationRulesForFieldType( $fieldType, $nullable ) {
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
}