<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Trees plugin Boot
* ----------------------------------------------
* @package Trees 2025
*/


/*
 |----------------------------------------------------------------------
 | Define Constants
 |----------------------------------------------------------------------
 */

define('BASE_PATH', dirname(__DIR__) . '/');
define('APP_PATH', BASE_PATH . 'app/');
define('CONFIG_PATH', BASE_PATH . 'config/');
define('PUBLIC_PATH', BASE_PATH . 'public/');
define('STORAGE_PATH', BASE_PATH . 'storage/');
define('VENDOR_PATH', BASE_PATH . 'vendor/');

/*
 |----------------------------------------------------------------------
 | Autoload Dependencies
 |----------------------------------------------------------------------
 |
 | Here we are loading the Composer autoloader to manage our dependencies.
 */
require VENDOR_PATH . 'autoload.php';

/*
 |----------------------------------------------------------------------
 | Initialize Application
 |----------------------------------------------------------------------
 |
 | Here we can initialize the application, set up configurations, and
 | prepare any services needed for the application to run.
 */
$app = new Trees\Trees(BASE_PATH);

/*
 |----------------------------------------------------------------------
 | Make $app a glbal variable
 |----------------------------------------------------------------------
 */

global $app;

/*
 |----------------------------------------------------------------------
 | Return the Application Instance
 |----------------------------------------------------------------------
 |
 | Finally, we return the application instance to be used in the entry point.
 */
return $app;
