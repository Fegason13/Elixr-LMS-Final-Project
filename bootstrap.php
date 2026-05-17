<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Application;

/**
 * Returns the application singleton after bootstrapping session and services.
 *
 * @return Application
 */
function app(): Application
{
    return Application::boot();
}

app();
