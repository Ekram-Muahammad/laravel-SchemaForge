# Laravel SchemaForge: Schema Design and CRUD Views

## Overview

**Laravel SchemaForge** is a powerful Laravel package designed to simplify and streamline the process of database schema design, CRUD view generation, and API resource method creation within Laravel applications. With SchemaForge, developers can effortlessly create, manage, and maintain their database schemas while simultaneously generating views for Create, Read, Update, and Delete (CRUD) operations. Additionally, it automates the creation of API resource methods, providing a comprehensive solution for Laravel development.

## Key Features

+ **Schema Design:** Laravel SchemaForge empowers developers to craft and manage database schemas effortlessly. Using a json, you can design your database tables with precision and efficiency.

+ **CRUD View Generation:** Simplify the creation of CRUD views for your Laravel application. SchemaForge automates the process, generating the necessary views for creating, reading, updating, and deleting records.

+ **API Resource Methods:** Generate API resource methods within your controllers, reducing manual coding efforts and accelerating the development of robust CRUD APIs.

+ **Data Seeding with Factory:** Automatically create seeders with factory data generators, allowing you to populate your database with sample data efficiently.

+ **Database Schema to JSON Conversion:** Easily convert your existing database schema to JSON files, enabling seamless migration and sharing of database structures.

+ **Automated Code Generation:** SchemaForge automates the generation of migration files, models, controllers, seeders, factories, and views for your CRUD operations based on your database schema.


## Benefits
+ **Efficiency:** Speed up your development workflow by seamlessly transitioning from schema design to code generation, data seeding, and schema cloning, all within a single package.

+ **Consistency:** Ensure consistency in your CRUD views, database schema, API resource methods, data seeding, and schema cloning, leading to more maintainable and organized code.

+ **Productivity:** Focus on building your application's logic and functionality, while SchemaForge handles the database, views, API, data seeding, and schema cloning layers.

## Installation

You can install this package via Composer. Run the following command:

```bash
composer require ArtisanCrudServiceProvider
```


## How to Use

The `php artisan make:crud` command is a powerful tool provided by Laravel SchemaForge that automates the creation of database schema and controllers with CRUD views based on the data provided in a JSON file. Follow these steps to use this command effectively:

### Step 1: Create a JSON File

Begin by creating a JSON file that contains the schema data for your database. This file should describe the tables and fields you want to generate. Here's an example of a basic JSON schema file:

```json
{
    "tableName": "users",
    "migration": true,
    "seeder": true,
    "seederNumRows": 10,
    "resourceController": true,
    "apiController": true,
    "views": true,
    "columns": [
        {
            "name": "name",
            "type": "string",
            "length": "255",
            "nullable": false,
            "unique": false,
            "defaultValue": "",
            "index": "",
            "hasRelation": false
        },
        {
            "name": "email",
            "type": "string",
            "length": "255",
            "nullable": false,
            "unique": true,
            "defaultValue": "",
            "index": "Unique",
            "hasRelation": false
        },
        {
            "name": "email_verified_at",
            "type": "timestamp",
            "length": "255",
            "nullable": true,
            "unique": false,
            "defaultValue": "",
            "index": "",
            "hasRelation": false
        },
        {
            "name": "password",
            "type": "string",
            "length": "255",
            "nullable": false,
            "unique": false,
            "defaultValue": "",
            "index": "",
            "hasRelation": false
        },
        {
            "name": "remember_token",
            "type": "string",
            "length": "100",
            "nullable": true,
            "unique": false,
            "defaultValue": "",
            "index": "",
            "hasRelation": false
        }
    ]
}
```