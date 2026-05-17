<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Exception\AccessDeniedException;
use App\Exception\NotFoundException;

$app = app();
$auth = $app->authService();
$auth->requireLogin();

$file = isset($_GET['file']) ? (string) $_GET['file'] : '';

try {
    $download = $app->downloadService()->resolveDownload($file, $auth->currentUser());
} catch (NotFoundException $exception) {
    http_response_code(404);
    echo htmlspecialchars($exception->getMessage());
    exit;
} catch (AccessDeniedException $exception) {
    http_response_code(403);
    echo htmlspecialchars($exception->getMessage());
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $download['file'] . '"');
header('Content-Length: ' . (string) filesize($download['path']));
readfile($download['path']);
exit;
