<?php

spl_autoload_register(function ($className) {
    $namespaces = explode('\\', $className);
    if (count($namespaces) > 1) {
        $classPath
            = APPLICATION_BASE_PATH
            . '/vendor/'
            . implode('/', $namespaces)
            . '.php';
        if (file_exists($classPath)) {
            require_once($classPath);
        }
    }
});

/* End of file autoload.php */