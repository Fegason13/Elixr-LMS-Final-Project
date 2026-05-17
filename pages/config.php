<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\AppConfig;

AppConfig::load();

if (!defined('DB_HOST')) {
    define('DB_HOST', AppConfig::getDbHost());
    define('DB_USER', AppConfig::getDbUser());
    define('DB_PASS', AppConfig::getDbPass());
    define('DB_NAME', AppConfig::getDbName());
    define('SITE_NAME', AppConfig::getSiteName());
    define('UPLOAD_DIR', AppConfig::getUploadDir());
}
