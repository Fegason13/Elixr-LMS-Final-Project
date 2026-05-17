<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$app = app();
$app->sessionManager()->destroy();
$app->redirector()->redirect('../index.php');
