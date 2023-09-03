<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd5f90f866511938b06cfa4fe27aea8be
{
    public static $files = array (
        'a4435621702ed5258c7494711326b7ba' => __DIR__ . '/../..' . '/src/Helpers/functions.php',
    );

    public static $prefixLengthsPsr4 = array (
        'E' => 
        array (
            'Ekram\\SchemaForge\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Ekram\\SchemaForge\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Ekram\\SchemaForge\\Helpers\\ApiController' => __DIR__ . '/../..' . '/src/Helpers/ApiController.php',
        'Ekram\\SchemaForge\\Helpers\\Factory' => __DIR__ . '/../..' . '/src/Helpers/Factory.php',
        'Ekram\\SchemaForge\\Helpers\\Field' => __DIR__ . '/../..' . '/src/Helpers/Field.php',
        'Ekram\\SchemaForge\\Helpers\\Migration' => __DIR__ . '/../..' . '/src/Helpers/Migration.php',
        'Ekram\\SchemaForge\\Helpers\\Model' => __DIR__ . '/../..' . '/src/Helpers/Model.php',
        'Ekram\\SchemaForge\\Helpers\\Reset' => __DIR__ . '/../..' . '/src/Helpers/Reset.php',
        'Ekram\\SchemaForge\\Helpers\\ResoureController' => __DIR__ . '/../..' . '/src/Helpers/ResoureController.php',
        'Ekram\\SchemaForge\\Helpers\\Seeder' => __DIR__ . '/../..' . '/src/Helpers/Seeder.php',
        'Ekram\\SchemaForge\\Helpers\\Validation' => __DIR__ . '/../..' . '/src/Helpers/Validation.php',
        'Ekram\\SchemaForge\\Helpers\\Views' => __DIR__ . '/../..' . '/src/Helpers/Views.php',
        'Ekram\\SchemaForge\\SchemaForgeServiceProvider' => __DIR__ . '/../..' . '/src/SchemaForgeServiceProvider.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd5f90f866511938b06cfa4fe27aea8be::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd5f90f866511938b06cfa4fe27aea8be::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd5f90f866511938b06cfa4fe27aea8be::$classMap;

        }, null, ClassLoader::class);
    }
}
