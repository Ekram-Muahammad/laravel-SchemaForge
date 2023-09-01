<?php
namespace Ekram\SchemaForge\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class Views {

    public function generateBladeViews( $tableName, $fields ) {

        $layoutPath = resource_path( 'views/layouts' );

        if ( !file_exists( $layoutPath ) ) {
            File::makeDirectory( $layoutPath );
        }

        $dashboardPath = resource_path( 'views/layouts/dashboard.blade.php' );

        if ( !file_exists( $dashboardPath ) ) {
            $dashboardStub = file_get_contents( __DIR__ . '/stubs/layouts/dashboard.stub' );

            $menu = file_get_contents( __DIR__ . '/stubs/component/menu.stub' );
            $menuContent = str_replace( [ '{{tableName}}' ], [ $tableName ], $menu );

            $menuContent = str_replace( [ '{{sidebarItems}}' ], [ $menuContent ], $dashboardStub );

            file_put_contents( $dashboardPath, $menuContent );
        } else {

            $dashboardContent = File::get( resource_path( 'views/layouts/dashboard.blade.php' ) );

            // Step 2: Modify the HTML Content
            $menu = file_get_contents( __DIR__ . '/stubs/component/menu.stub' );

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
            file_put_contents( $dashboardPath, $modifiedHtmlContent );
        }

        $folderPath = resource_path( 'views/pages' );

        if ( !file_exists( $folderPath ) ) {
            File::makeDirectory( $folderPath );
        }

        // Create the directory for the views if it doesn't exist
        $viewsDirectory = resource_path("views/pages/{$tableName}");
        if (!file_exists($viewsDirectory)) {
            File::makeDirectory($viewsDirectory);
        }

        // Generate the index.blade.php file

        $indexViewStub = file_get_contents(__DIR__ . '/stubs/views/index.stub');

        $headers = $this->generateTableHeaders($fields);
        $records = $this->generateTableColumns($fields);

        $indexContent = str_replace(['{{tableName}}', '{{headers}}', '{{records}}'], [$tableName, $headers, $records], $indexViewStub);

        file_put_contents("{$viewsDirectory}/index.blade.php", $indexContent);

        // Generate the create.blade.php file
        $createView = <<<EOT
        <!-- contents of create.blade.php -->
        EOT;
        file_put_contents("{$viewsDirectory}/create.blade.php", $createView);

        // Generate the edit.blade.php file
        $editView = <<<EOT
        <!-- contents of edit.blade.php -->
        EOT;
        file_put_contents("{$viewsDirectory}/edit.blade.php", $editView);

        // Generate the show.blade.php file
        $showView = <<<EOT
        <!-- contents of show.blade.php -->
        EOT;
        file_put_contents("{$viewsDirectory}/show.blade.php", $showView);



        $showViewStub = file_get_contents(__DIR__ . '/stubs/views/show.stub');

        $data = $this->generateFieldShow($fields);

        $showContent = str_replace(['{{tableName}}', '{{headers}}', '{{fields}}'], [$tableName, $headers, $data], $showViewStub);

        file_put_contents("{$viewsDirectory}/show.blade.php", $showContent);

    }


    public function generateFieldShow($fields)
    {
        $headers = '';
        foreach ($fields as $field) {
            $headers .= "<h4>{{\$record->" . $field['fieldName'] . "}}</h4>\n     ";
        }
        return $headers;
    }


    public function generateTableHeaders($fields)
    {
        $headers = '';
        foreach ($fields as $field) {
            $headers .= "<th>{$field['fieldName']}</th>\n        ";
        }
        return $headers;
    }

    public function generateTableColumns($fields)
    {
        $columns = '';
        foreach ($fields as $field) {
            $columns .= "<td>{{ \$record->{$field['fieldName']} }}</td>\n                    ";
        }
        return $columns;
    }

    public function generateFormFields($fields, $tableName)
    {
        $formFields = '';
        foreach ($fields as $field) {
            $formFields .= $this->generateFormField($field);
        }

        $createStub = file_get_contents(__DIR__ . '/stubs/views/create.stub');
        $createView = str_replace(['{{tableName}}', '{{formFields}}'], [$tableName, $formFields], $createStub);


        $updateStub =file_get_contents(__DIR__ . '/stubs/views/edit.stub');
        $updateView = str_replace(['{{tableName}}', '{{formFields}}', '{{varName}}'], [$tableName, $formFields, Str::singular($tableName)], $updateStub);


        $viewsDirectory = resource_path("views/pages/{$tableName}");
        file_put_contents("{$viewsDirectory}/create.blade.php", $createView);
        file_put_contents("{$viewsDirectory}/edit.blade.php", $updateView);
    }

    public function generateFormField($field)
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

    public function generateDateField($field)
    {

        $dateStub = file_get_contents(__DIR__ . '/stubs/form/date.stub');
        $dateField = str_replace(['{{fieldName}}'], [$field['fieldName']], $dateStub);


        return $dateField;

    }

    public function generateDatetimeField($field)
    {

        $dateStub = file_get_contents(__DIR__ . '/stubs/form/dateTime.stub');
        $dateField = str_replace(['{{$fieldName}}'], [$field['fieldName']], $dateStub);

        return $dateField;

    }

    public function generateTextareaField($field)
    {
        $textStub = file_get_contents(__DIR__ . '/stubs/form/textArea.stub');
        $textField = str_replace(['{{$fieldName}}'], [$field['fieldName']], $textStub);

        return $textField;
    }

    public function generateNumberField($field)
    {
        $numberStub = file_get_contents(__DIR__ . '/stubs/form/number.stub');
        $numberField = str_replace(['{{$fieldName}}'], [$field['fieldName']], $numberStub);

        return $numberField;
    }

    public function generateEnumField($field)
    {
        $enumValues = $field['fieldProperties']['enumValues'];

        $options = "";
        foreach ($enumValues as $value) {
            $options .= "<option value=\"$value\">$value</option>";
        }
        $enumStub = file_get_contents(__DIR__ . '/stubs/form/enum.stub');
        $enumField = str_replace(['{{$fieldName}}', '{{options}}'], [$field['fieldName'], $options], $enumStub);

        return $enumField;

    }

    public function generateSelectField($field)
    {
        $relatedModel = '\\App\\Models\\' . Str::studly(Str::ucfirst($field['relatedTable']));
        $selectOptions = "$relatedModel::pluck('id')";

        $selectStub = file_get_contents(__DIR__ . '/stubs/form/select.stub');
        $selectField = str_replace(['{{$fieldName}}', '{{selectOptions}}'], [$field['fieldName'], $selectOptions], $selectStub);

        return $selectField;

    }

    public function generateTextField($field)
    {
        $textStub = file_get_contents(__DIR__ . '/stubs/form/text.stub');
        $textField = str_replace(['{{$fieldName}}'], [$field['fieldName']], $textStub);

        return $textField;
    }

    public function generateBooleanField($field)
    {
        $radioStub = file_get_contents(__DIR__ . '/stubs/form/radio.stub');
        $radioField = str_replace(['{{$fieldName}}'], [$field['fieldName' ] ], $radioStub );

        return $radioField;                                                        
    }
                                                                                        
}