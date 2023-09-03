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
            $menuContent = preg_replace( [ '/{{\s*tableName\s*}}/' ], [ $tableName ], $menu );

            $menuContent = preg_replace( [ '/{{\s*sidebarItems\s*}}/' ], [ $menuContent ], $dashboardStub );

            file_put_contents( $dashboardPath, $menuContent );
        } else {

            $dashboardContent = File::get( resource_path( 'views/layouts/dashboard.blade.php' ) );

            // Step 2: Modify the HTML Content
            $menu = file_get_contents( __DIR__ . '/stubs/component/menu.stub' );

            $menuContent = preg_replace( [ '/{{\s*tableName\s*}}/' ], [ $tableName ], $menu );

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

        $indexContent = preg_replace(['/{{\s*tableName\s*}}/', '/{{\s*headers\s*}}/', '/{{\s*records\s*}}/'], [$tableName, $headers, $records], $indexViewStub);

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

        $showContent = preg_replace(['/{{\s*tableName\s*}}/', '/{{\s*headers\s*}}/', '/{{\s*fields\s*}}/'], [$tableName, $headers, $data], $showViewStub);

        file_put_contents("{$viewsDirectory}/show.blade.php", $showContent);

    }


    public function generateFieldShow($fields)
    {
        $headers = '';
        foreach ($fields as $field) {
            $headers .= "<h4>{{\$record->" . $field['fieldName'] . "}}</h4>".PHP_EOL."     ";
        }
        return $headers;
    }


    public function generateTableHeaders($fields)
    {
        $headers = '';
        foreach ($fields as $field) {
            $headers .= "<th>{$field['fieldName']}</th>".PHP_EOL."        ";
        }
        return $headers;
    }

    public function generateTableColumns($fields)
    {
        $columns = '';
        foreach ($fields as $field) {
            $columns .= "<td>{{ \$record->{$field['fieldName']} }}</td>".PHP_EOL."                    ";
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
        $createView = preg_replace(['/{{\s*tableName\s*}}/', '/{{\s*formFields\s*}}/'], [$tableName, $formFields], $createStub);


        $updateStub =file_get_contents(__DIR__ . '/stubs/views/edit.stub');
        $updateView = preg_replace(['/{{\s*tableName\s*}}/', '/{{\s*formFields\s*}}/', '/{{\s*varName\s*}}/'], [$tableName, $formFields, Str::singular($tableName)], $updateStub);


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
        $dateField = preg_replace(['/{{\s*fieldName\s*}}/'], [$field['fieldName']], $dateStub);


        return $dateField;

    }

    public function generateDatetimeField($field)
    {

        $dateStub = file_get_contents(__DIR__ . '/stubs/form/dateTime.stub');
        $dateField = preg_replace(['/{{\s*fieldName\s*}}/'], [$field['fieldName']], $dateStub);

        return $dateField;

    }

    public function generateTextareaField($field)
    {
        $textStub = file_get_contents(__DIR__ . '/stubs/form/textArea.stub');
        $textField = preg_replace(['/{{\s*fieldName\s*}}/'], [$field['fieldName']], $textStub);

        return $textField;
    }

    public function generateNumberField($field)
    {
        $numberStub = file_get_contents(__DIR__ . '/stubs/form/number.stub');
        $numberField = preg_replace(['/{{\s*fieldName\s*}}/'], [$field['fieldName']], $numberStub);

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
        $enumField = preg_replace(['/{{\s*fieldName\s*}}/', '/{{\s*options\s*}}/'], [$field['fieldName'], $options], $enumStub);

        return $enumField;

    }

    public function generateSelectField($field)
    {
        $relatedModel = '\\App\\Models\\' . Str::studly(Str::ucfirst($field['relatedTable']));
        $selectOptions = "$relatedModel::pluck('id')";

        $selectStub = file_get_contents(__DIR__ . '/stubs/form/select.stub');
        $selectField = preg_replace(['/{{\s*fieldName\s*}}/', '/{{\s*selectOptions\s*}}/'], [$field['fieldName'], $selectOptions], $selectStub);

        return $selectField;

    }

    public function generateTextField($field)
    {
        $textStub = file_get_contents(__DIR__ . '/stubs/form/text.stub');
        $textField = preg_replace(['/{{\s*fieldName\s*}}/'], [$field['fieldName']], $textStub);

        return $textField;
    }

    public function generateBooleanField($field)
    {
        $radioStub = file_get_contents(__DIR__ . '/stubs/form/radio.stub');
        $radioField = preg_replace(['/{{\s*fieldName\s*}}/'], [$field['fieldName' ] ], $radioStub );

        return $radioField;                                                        
    }
                                                                                        
}