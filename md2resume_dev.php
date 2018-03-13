<?php
error_reporting(E_ALL | E_STRICT);

// If the dependencies aren't installed, we have to bail and offer some help.
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    exit("\nPlease run `composer install` to install dependencies.\n\n");
}

// Bootstrap our application with the Composer autoloader
$app = require __DIR__ . '/vendor/autoload.php';

// Setup the namespace for our own namespace
$app->add('Resume', __DIR__ . '/src');

// Instantiate our Console application
$console = new Resume\Cli\Resume();

// If we're running from phar, we get these values from the stub
if (!defined('IN_PHAR')) {
    $project = json_decode(file_get_contents(__DIR__ . '/composer.json'));
}

$templatePath = __DIR__ . '/templates';
$consoleTemplatePath = __DIR__ . '/src/Resume/Templates';

// Init the app with these params
$console->initialize($templatePath, $consoleTemplatePath, $project);

// Execute the console app.
$console->run();

/* End of resume.php */
