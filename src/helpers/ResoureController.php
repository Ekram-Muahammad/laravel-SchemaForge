<?php
namespace Ekram\ArtisanCrud\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Ekram\ArtisanCrud\Helpers\Validation;

class ResoureController {
    public function generateController( $tableName, $fields, $modelName ) {
        $controllerName = ucfirst( Str::camel( Str::singular( $tableName ) ) ) . 'Controller';

        // Run the make:controller command
        Artisan::call( 'make:controller', [
            'name' => $controllerName,
        ] );

        $controllerContent = $this->generateResourceMethods( $fields, $modelName, $controllerName, $tableName );

        $controllerPath = app_path() . '/Http/Controllers/' . $controllerName . '.php';

        file_put_contents( $controllerPath, $controllerContent );

        $this->generateResoutceRoutes( $tableName, $controllerName );

    }

    public function generateResourceMethods( $fields, $modelName, $controllerName, $tableName ) {
        // Generate logic for each resource function based on the table fields
        $resourceMethods = [
            'index' => $this->generateIndexMethod( $fields, $modelName, $tableName ),
            'show' => $this->generateShowMethod( $fields, $modelName, $tableName ),
            'create' => $this->generateCreateMethod( $fields, $modelName, $tableName ),
            'store' => $this->generateStoreMethod( $fields, $modelName, $tableName ),
            'edit' => $this->generateEditMethod( $fields, $modelName, $tableName ),
            'update' => $this->generateUpdateMethod( $fields, $modelName, $tableName ),
            'destroy' => $this->generateDestroyMethod( $fields, $modelName, $tableName ),
        ];

        // Get the controller stub content
        $stubContent = file_get_contents( __DIR__ . '/stubs/controller.stub' );

        // Replace placeholders with generated methods
        foreach ( $resourceMethods as $method => $logic ) {
            $stubContent = str_replace( "{{{$method}Method}}", $logic, $stubContent );
        }

        $stubContent = str_replace( [ '{{ControllerName}}', '{{ModelName}}' ], [ $controllerName, $modelName ], $stubContent );

        return $stubContent;
    }

    public function generateIndexMethod( $fields, $modelName, $tableName ) {

        $indexStub = file_get_contents( __DIR__ . '/stubs/methods/resources/index.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $indexStub );

    }

    public function generateShowMethod( $fields, $modelName, $tableName ) {

        $showStub = file_get_contents( __DIR__ . '/stubs/methods/resources/show.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $showStub );

    }

    public function generateCreateMethod( $fields, $modelName, $tableName ) {

        $createStub = file_get_contents( __DIR__ . '/stubs/methods/resources/create.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $createStub );

    }

    public function generateStoreMethod( $fields, $modelName, $tableName ) {

        $storeLogic = '';

        $validationRules = [];

        foreach ( $fields as $field ) {
            $fieldType = $field[ 'fieldType' ];
            $isNullabe = $field[ 'nullable' ];
            $fieldName = $field[ 'fieldName' ];
            $validation = new Validation();
            $rules = $validation->generateValidationRulesForFieldType( $fieldType, $isNullabe );
            $validationRules[ $fieldName ] = $rules;
        }

        // Generate validation logic
        $validationLogic = '';
        foreach ( $validationRules as $field => $rules ) {
            $rulesString = implode( '|', $rules );
            $validationLogic .= "\n    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $storeLogic .= "  '{$fieldName}' => \$request->{$fieldName},\n";
        }

        $storeStub = file_get_contents( __DIR__ . '/stubs/methods/resources/store.stub' );

        return str_replace( [ '{{ModelName}}', '{{StoreLogic}}', '{{tableName}}', '{{validationLogic}}' ], [ $modelName, $storeLogic, $tableName, $validationLogic ], $storeStub );

    }

    public function generateEditMethod( $fields, $modelName, $tableName ) {

        $editStub = file_get_contents( __DIR__ . '/stubs/methods/resources/edit.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}' ], [ $modelName, $tableName, ], $editStub );

    }

    public function generateUpdateMethod( $fields, $modelName, $tableName ) {

        $updateLogic = '';

        $validationRules = [];

        foreach ( $fields as $field ) {
            $fieldType = $field[ 'fieldType' ];
            $isNullabe = $field[ 'nullable' ];
            $fieldName = $field[ 'fieldName' ];
            $validation = new Validation();
            $rules = $validation->generateValidationRulesForFieldType( $fieldType, $isNullabe );
            $validationRules[ $fieldName ] = $rules;
        }

        // Generate validation logic
        $validationLogic = '';
        foreach ( $validationRules as $field => $rules ) {
            $rulesString = implode( '|', $rules );
            $validationLogic .= "\n    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $updateLogic .= "  '{$fieldName}' => \$request->{$fieldName},\n";
        }

        $updateStub = file_get_contents( __DIR__ . '/stubs/methods/resources/update.stub' );

        return str_replace( [ '{{ModelName}}', '{{UpdateLogic}}', '{{tableName}}', '{{varName}}', '{{validationLogic}}' ], [ $modelName, $updateLogic, $tableName, Str::singular( $tableName ), $validationLogic ], $updateStub );

    }

    public function generateDestroyMethod( $fields, $modelName, $tableName ) {

        $destroyStub = file_get_contents( __DIR__ . '/stubs/methods/resources/destroy.stub' );

        return str_replace( [ '{{ModelName}}', '{{tableName}}', '{{varName}}' ], [ $modelName, $tableName, Str::singular( $tableName ) ], $destroyStub );

    }

    public function generateResoutceRoutes( $tableName, $controllerName ) {
        $routesFilePath = base_path( 'routes/web.php' );

        $routeontent = file_get_contents( base_path( 'routes/web.php' ) );

        $importStatement = "<?php \n use App\Http\Controllers\\$controllerName; \n";
        $routeApiContent = str_replace( [ '<?php' ], [ $importStatement ], $routeontent );
        file_put_contents( $routesFilePath, $routeApiContent );

        $resourceRouteStub = file_get_contents( __DIR__ . '/stubs/routes/web.stub' );
        $routeContent = str_replace( [ '{{controllerName}}', '{{tableName}}' ], [ $controllerName, $tableName ], $resourceRouteStub );
        File::append( $routesFilePath, $routeContent );
    }
}