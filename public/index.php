<?php

declare(strict_types=1);

/*
* ------------------------------------------------
* Index Page (Public)
* ------------------------------------------------
* @package Trees 2025
*/


/*
 |----------------------------------------------------------------------
 | Load the Bootstrap File
 |----------------------------------------------------------------------
 |
 | Here we are loading the bootstrap file to set up the application.
 */
require __DIR__ . '/../bootstrap/boot.php';

/*
 |----------------------------------------------------------------------
 | Run the Application
 |----------------------------------------------------------------------
 |
 | Now we can run the application. This is where the main logic of
 | your application will be executed.
 */

try {
    $app->run();

    // Handle request
    $response = $app->handle();

    // Send response
    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header("$name: $value", false);
        }
    }
    echo $response->getBody();
} catch (Exception $e) {
    throw $e;
}
