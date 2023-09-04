# Laravel SchemaForge

## Overview

**Laravel SchemaForge** is a powerful Laravel package designed to simplify and streamline the process of database schema design, CRUD view generation, and API resource method creation within Laravel applications. With SchemaForge, developers can effortlessly create, manage, and maintain their database schemas while simultaneously generating views for Create, Read, Update, and Delete (CRUD) operations. Additionally, it automates the creation of API resource methods, providing a comprehensive solution for Laravel development.

## Key Features

+ **Schema Design:** Laravel SchemaForge empowers developers to craft and manage database schemas effortlessly. Using a json, you can design your database tables with precision and efficiency.

+ **CRUD View Generation:** Simplify the creation of CRUD views for your Laravel application. SchemaForge automates the process, generating the necessary views for creating, reading, updating, and deleting records.

+ **API Resource Methods:** Generate API resource methods within your controllers, reducing manual coding efforts and accelerating the development of robust CRUD APIs.

+ **Validating Data Based on Column Types:** Laravel SchemaForge goes a step further in simplifying data validation by automatically generating validation rules based on the column types defined in your database schema. This feature ensures that incoming data adheres to the expected data types, enhancing data integrity and security.

+ **Data Seeding with Factory:** Automatically create seeders with factory data generators, allowing you to populate your database with sample data efficiently.

+ **Database Schema to JSON Conversion:** Easily convert your existing database schema to JSON files, enabling seamless migration and sharing of database structures.

+ **Automated Code Generation:** SchemaForge automates the generation of migration files, models, controllers, seeders, factories, and views for your CRUD operations based on your database schema.

+ **Automated User Interface Generation:** It generates CRUD (Create, Read, Edit) pages, automating the creation of user interfaces for displaying, editing, creating, and deleting data within your Laravel application.

## Benefits
+ **Efficiency:** Speed up your development workflow by seamlessly transitioning from schema design to code generation, data seeding, and schema cloning, all within a single package.

+ **Consistency:** Ensure consistency in your CRUD views, database schema, API resource methods, data seeding, and schema cloning, leading to more maintainable and organized code.

+ **Productivity:** Focus on building your application's logic and functionality, while SchemaForge handles the database, views, API, data seeding, and schema cloning layers.

## Installation

You can install this package via Composer. Run the following command:

```bash
composer require SchemaForgeServiceProvider
```

Put following class in providers array of your config/app.php file:

```php
'providers' => 
[ 
   // ... 
   Ekram\SchemaForge\SchemaForgeServiceProvider, 
];

```

Now run the following command to export files from vendor folder:


```bash
php artisan vendor:publish --provider="Ekram\SchemaForge\SchemaForgeServiceProvider"
```


## How to Use

The `php artisan make:crud` command is a powerful tool provided by Laravel SchemaForge that automates the creation of database schema and controllers with CRUD views based on the data provided in a JSON file. Follow these steps to use this command effectively:

### Step 1: Create a JSON File

+ On your Laravel project's root directory, create a new directory named `schemas` if it doesn't already exist. This directory will serve as the location for your JSON schema files.

+ Begin by creating a JSON file that contains the schema data for your database. This file should describe the tables and fields you want to generate. Here's an example of a basic JSON schema file:

```json
{
    "tableName": "users",                // The name of the table.
    "migration": true,                   // Set to true if you want to create a migration for this table.
    "seeder": true,                      // Set to true if you want to generate a seeder for this table.
    "seederNumRows": 10,                 // Number of rows to be generated in the seeder.
    "resourceController": true,          // Set to true if you want to create a resource controller.
    "apiController": true,               // Set to true if you want to create an API controller.
    "views": true,                       // Set to true if you want to generate views for this table.
    "columns": [
        {
            "name": "name",              // Column name for the user's name.   
            "type": "string",            // Data type for the column (e.g., "string")
            "length": "255",             // length for the Column (e.g., "255").
            "nullable": false,           // Whether the column can be nullable (true or false).
            "unique": false,             // Whether the column should have a unique constraint (true or false).
            "defaultValue": "",          // Default value for the column (empty string if none).
            "index": "",                 // Index type for the column (e.g., "Unique" or ""). Leave empty if no index.
            "hasRelation": false,        // Whether this column has a relationship to another table (true or false).
            "relation":{
                "relationType":"",       // Type of relationship (e.g., "belongsTo", "hasMany", "belongsToMany").

                // type of relations
                // "hasOne, belongsTo, hasMany, belongsToMany, morphTo, morphMany, morphToMany, hasOneThrough, hasManyThrough" 

               
                "relatedTable":"",       // Name of the related table.
                "relatedColumn":"",      // Name of the related column in the related table.

                // Define a "hasManyThrough" relationship with the "tags" table.
                "intermediateModel":"",  // Name of the intermediate model (if applicable, otherwise leave empty).
                "targetModel":""         // Name of the target model (if applicable, otherwise leave empty).
            }
        },
        {
            "name": "email",
            "type": "string",
            "length": "255",
            "nullable": false,
            "unique": true,
            "defaultValue": "",
            "index": "Unique",
         
        },
        {
            "name": "password",
            "type": "string",
            "length": "255",
            "nullable": false,
            "unique": false,
            "defaultValue": "",
            "index": "",
            "hasRelation": false,
            "relatedTable":"",
            "relatedColumn":""
        }
    ]
}
```

### Step 2: Run the Command
+ Once you have your JSON schema file ready, open your terminal and run the following command:

```bash
php artisan make:crud your-json-file-name [action]
```

+ Replace your-json-file-name with the actual name of your JSON file.

## [action]: This parameter is optional and accepts two values:

+ update: Use this option to update the generated files. It will override existing files with the newly generated ones if there are any changes in the JSON schema or if you want to refresh the generated code.

+ reset: Use this option to reset the generated files. It will delete all previously generated files, ensuring a clean and fresh set of code files.


### Step 3: Review the Generated Files
After executing the command, Laravel SchemaForge will generate several files and directories for you. These include:

+ Database migrations for creating the specified tables and fields.

+ Laravel SchemaForge generates a primary API controller inside the app/Http/Controllers/Api directory. This controller serves as the entry point for your API endpoints.

+ additionally, it creates a separate controller for API resource methods. This controller is also placed inside the app/Http/Controllers/ directory. It provides methods for handling standard API CRUD operations like fetching, creating, updating, and deleting records.

+ Blade view files for creating, reading, updating, and deleting records, inside the resources/views/{tableName} directory.

+ Seeder and Factory files for populating your database with sample data.

+ Laravel SchemaForge automatically configures the routes for your API in the routes/api.php file. It defines the necessary routes to access the API endpoints.

+ Furthermore, it ensures that API routes are also registered in the routes/web.php file. This enables you to access the API endpoints through both the API and web routes.


# Command: php artisan db:clone tableName

```bash
php artisan db:clone tableName
```

The php artisan db:clone command is a powerful utility provided by Laravel SchemaForge that allows you to clone all your database schema tables into JSON files, storing them in the `schemas` directory. This command serves as the foundation for creating various features described earlier, such as generating migrations, models, controllers, views, API resource methods, and more, based on your existing database structure.

- `[tablename]` (optional): Specifies the name of the table you want to clone. If provided, the `db:clone` command will capture only the schema details of the specified table and save it as a JSON file in the `schemas` directory.


#### Example:

To clone only the "products" table from the database, you can run the following command:

```bash
php artisan db:clone products
```

## Purpose:

The primary purpose of the db:clone command is to capture your current database schema and save it in a structured JSON format. This captured schema serves as a blueprint for generating code and features within your Laravel application. Here's how it works:

+ Capture Database Schema: When you run `php artisan db:clone`, the command inspects your database and extracts information about the tables, columns, relationships, and other schema details.

+ JSON File Creation: It then converts this schema information into JSON files, with each JSON file representing a database table. These JSON files are saved in the `schemas` directory within your Laravel project.

+ Foundation for Features: The JSON files generated by `db:clone` become the foundation for other features provided by Laravel SchemaForge. For instance, when you use the `php artisan make:crud` command, it reads the JSON schema files from the `schemas` directory to automate the creation of migrations, models, controllers, views, API resource methods, seeders, factories, and more, based on your database structure.



## Custom Features and Special Designs

At Laravel SchemaForge, we understand that your project might have unique requirements or require custom designs. If you need additional features or specialized design work for your Laravel project, we're here to help!

- Custom Features: If you require specific functionalities that are not part of the standard Laravel SchemaForge package, don't hesitate to reach out to us. We can discuss your needs and provide solutions tailored to your project.

- Special Designs: Need a distinctive look and feel for your project's views? Our team can create custom designs that align perfectly with your project's branding and requirements.

To explore these options or discuss your customization needs, please feel free to contact us:

- Email: [ekram.developer@gmail.com](mailto:ekram.developer@gmail.com)
- Phone: +964 750 180 4528

We're committed to helping you achieve your project goals with Laravel SchemaForge, whether it's through our standard features or by providing custom solutions.
This section encourages users to contact you if they require additional features or unique designs, emphasizing that you're open to customization and ready to assist with their specific project needs.



## Contact Information

If you have any questions, encounter issues, or need assistance with the Laravel SchemaForge package, please feel free to reach out to us. You can contact us via email at:

- Email: [ekram.developer@gmail.com](mailto:ekram.developer@gmail.com)
- Phone: +964 750 180 4528

We value your feedback and are here to help you make the most of our package.