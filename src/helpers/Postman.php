<?php
namespace Ekram\SchemaForge\Helpers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class Postman
{

    public function generateCollection($tableName, $fields)
    {

        $postmanPath = base_path('schemas/postman');

        if (!file_exists($postmanPath)) {
            File::makeDirectory($postmanPath);
        }

        $collectionPath = base_path("schemas/postman/{$tableName}.json");

        $info = [
            "_postman_id" => uniqid(),
            "name" => "{$tableName} Collection",
            "description" => "{$tableName} CRUD operation",
            "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
        ];

        $jsonInfo = json_encode($info);

        $collection = "{
         \"info\":{$jsonInfo},
         \"item\":[
            {$this->generateFindRequest($tableName)},
            {$this->generateFindAllRequest($tableName)},
            {$this->generateDeleteRequest($tableName)},
            {$this->generateCreateRequest($tableName,$fields)},
            {$this->generateUpdateRequest($tableName,$fields)}
        ]}";



        file_put_contents($collectionPath, $collection);

    }


    public function generateFindRequest($tableName)
    {

        $findStub = file_get_contents(__DIR__ . '/stubs/postman/find.stub');
        $findRequest = preg_replace(['/{{\s*tableName\s*}}/'], [$tableName], $findStub);


        return $findRequest;

    }

    public function generateFindAllRequest($tableName)
    {

        $findStub = file_get_contents(__DIR__ . '/stubs/postman/findAll.stub');
        $findAllRequest = preg_replace(['/{{\s*tableName\s*}}/'], [$tableName], $findStub);


        return $findAllRequest;

    }

    public function generateDeleteRequest($tableName)
    {

        $deleteStub = file_get_contents(__DIR__ . '/stubs/postman/delete.stub');
        $deleteRequest = preg_replace(['/{{\s*tableName\s*}}/'], [$tableName], $deleteStub);


        return $deleteRequest;

    }

    public function generateCreateRequest($tableName, $fields)
    {

        $body = [];

        foreach ($fields as $field) {
            $body[$field['fieldName']]= "";
        }


        $createStub = file_get_contents(__DIR__ . '/stubs/postman/create.stub');
        $createequest = preg_replace(['/{{\s*tableName\s*}}/', '/{{\s*body\s*}}/'], [$tableName, addslashes(json_encode($body,JSON_UNESCAPED_SLASHES))], $createStub);


        return $createequest;

    }

    public function generateUpdateRequest($tableName, $fields)
    {

        $body = [];

        foreach ($fields as $field) {
            $body[$field['fieldName']]= "";
        }


   

        $updatetub = file_get_contents(__DIR__ . '/stubs/postman/update.stub');
        $updateRequest = preg_replace(['/{{\s*tableName\s*}}/', '/{{\s*body\s*}}/'], [$tableName, addslashes(json_encode($body,JSON_UNESCAPED_SLASHES))], $updatetub);


        return $updateRequest;

    }


}