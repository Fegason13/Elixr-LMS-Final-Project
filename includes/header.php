<?php

declare(strict_types=1);

use App\Config\AppConfig;

$app = app();
$auth = $app->authService();
$flash = $app->flashMessage()->get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars(AppConfig::getSiteName()); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php if ($auth->isLoggedIn()): ?>
    <nav class="navbar">
        <a href="dashboard.php" class="logo"><?php echo htmlspecialchars(AppConfig::getSiteName()); ?></a>
        <div class="nav-links">
            <a href="dashboard.php">Home</a>
            <?php if ($auth->isStudent()): ?>
            <a href="join_class.php">Join Class</a>
            <?php endif; ?>
            <?php if ($auth->isTeacher()): ?>
            <a href="create_class.php">Create Class</a>
            <?php endif; ?>
            <span class="user-name"><?php echo htmlspecialchars($auth->currentUser()->getName()); ?></span>
            <a href="logout.php" class="btn btn-small">Logout</a>
        </div>
    </nav>
    <?php endif; ?>

    <main class="container">
        <?php if ($flash !== null): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
        <?php endif; ?>
