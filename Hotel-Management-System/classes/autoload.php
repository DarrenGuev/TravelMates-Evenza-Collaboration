<?php

//Autoloader for class files
//Automatically loads class files when they are instantiated.

spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/' . $className . '.php';
    
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Load environment variables
require_once __DIR__ . '/../dbconnect/load_env.php';
