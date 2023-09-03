<?php
namespace Ekram\SchemaForge\Helpers;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Ekram\SchemaForge\Helpers\Validation;

class ApiController {
    public function generateApiController( $tableName, $fields, $modelName ) {
        $controllerName = ucfirst( Str::camel( Str::singular( $tableName ) ) ) . 'Controller';

        // Run the make:controller command
        Artisan::call( 'make:controller', [
            'name' => 'Api/' . $controllerName,
        ] );

        $controllerContent = $this->generateApiMethods( $fields, $modelName, $controllerName, $tableName );

        $controllerPath = app_path() . '/Http/Controllers/Api/' . $controllerName . '.php';

        file_put_contents( $controllerPath, $controllerContent );

        $this->generateApiRoutes( $tableName, $controllerName );

    }

    protected function generateApiMethods( $fields, $modelName, $controllerName, $tableName ) {
        // Generate logic for each resource function based on the table fields
        $apiMethods = [
            'find' => $this->generateApiFindMethod( $fields, $modelName, $tableName ),
            'findAll' => $this->generateApiFindAllMethod( $fields, $modelName, $tableName ),
            'store' => $this->generateApiStorMethod( $fields, $modelName, $tableName ),
            'update' => $this->generateApiUpdateMethod( $fields, $modelName, $tableName ),
            'delete' => $this->generateApiDeleteMethod( $fields, $modelName, $tableName ),
        ];

        // Get the controller stub content
        $stubContent = file_get_contents( __DIR__ . '/stubs/apiController.stub' );

        // Replace placeholders with generated methods
        foreach ( $apiMethods as $method => $logic ) {
            $stubContent = str_replace( "{{{$method}}}", $logic, $stubContent );
        }

        $stubContent = preg_replace( [ '/{{\s*ControllerName\s*}}/', '/{{\s*ModelName\s*}}/' ], [ $controllerName, $modelName ], $stubContent );

        return $stubContent;
    }

    protected function generateApiFindMethod( $fields, $modelName, $tableName ) {

        $findStub = file_get_contents( __DIR__ . '/stubs/methods/api/find.stub' );

        return preg_replace( [ '/{{\s*ModelName\s*}}/', '/{{\s*tableName\s*}}/' ], [ $modelName, $tableName, ], $findStub );

    }

    protected function generateApiFindAllMethod( $fields, $modelName, $tableName ) {

        $findAllStub = file_get_contents( __DIR__ . '/stubs/methods/api/findAll.stub' );

        return preg_replace( [ '/{{\s*ModelName\s*}}/', '/{{\s*tableName\s*}}/' ], [ $modelName, $tableName, ], $findAllStub );

    }

    protected function generateApiStorMethod( $fields, $modelName, $tableName ) {

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
            $validationLogic .= PHP_EOL."    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $storeLogic .= "  '{$fieldName}' => \$request->{$fieldName},".PHP_EOL;
        }

        $storeStub = file_get_contents( __DIR__ . '/stubs/methods/api/store.stub' );

        return preg_replace( [ '/{{\s*ModelName\s*}}/', '/{{\s*StoreLogic\s*}}/', '/{{\s*tableName\s*}}/', '/{{\s*validationLogic\s*}}/' ], [ $modelName, $storeLogic, $tableName, $validationLogic ], $storeStub );

    }

    protected function generateApiUpdateMethod( $fields, $modelName, $tableName ) {

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
            $validationLogic .= PHP_EOL."    '{$field}' => '{$rulesString}',";
        }

        foreach ( $fields as $field ) {
            $fieldName = $field[ 'fieldName' ];
            $updateLogic .= "  '{$fieldName}' => \$request->{$fieldName},".PHP_EOL;
        }

        $updateStub = file_get_contents( __DIR__ . '/stubs/methods/api/update.stub' );

        return preg_replace( [ '/{{\s*ModelName\s*}}/', '/{{\s*UpdateLogic\s*}}/', '/{{\s*tableName\s*}}/', '/{{\s*varName\s*}}/', '/{{\s*validationLogic\s*}}/' ], [ $modelName, $updateLogic, $tableName, Str::singular( $tableName ), $validationLogic ], $updateStub );

    }

    protected function generateApiDeleteMethod( $fields, $modelName, $tableName ) {

        $deleteStub = file_get_contents( __DIR__ . '/stubs/methods/api/delete.stub' );

        return preg_replace( [ '/{{\s*ModelName\s*}}/', '/{{\s*tableName\s*}}/', '/{{\s*varName\s*}}/' ], [ $modelName, $tableName, Str::singular( $tableName ) ], $deleteStub );

    }

    protected function generateApiRoutes( $tableName, $controllerName ) {
        $routesFilePath = base_path( 'routes/api.php' );

        $apiContent = file_get_contents( base_path( 'routes/api.php' ) );

        $importStatement = "<?php ".PHP_EOL." use App\Http\Controllers\Api\\$controllerName; ".PHP_EOL;

        $routeApiContent = str_replace( [ '<?php' ], [ $importStatement ], $apiContent );

        file_put_contents( $routesFilePath, $routeApiContent );

        $apiRouteStub = file_get_contents( __DIR__ . '/stubs/routes/api.stub' );

        $routeContent = preg_replace( [ '/{{\s*controllerName\s*}}/', '/{{\s*tableName\s*}}/' ], [ $controllerName, $tableName ], $apiRouteStub );

        // Append the generated route content to api.php
        File::append( $routesFilePath, $routeContent );

    }
}