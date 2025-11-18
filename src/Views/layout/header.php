<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($appConfig['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <script>window.APP_BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>/?route=leads"><i class="bi bi-radar"></i> AI Client Hunter</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <?php if ($authUser): ?>
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?= ($_GET['route'] ?? 'leads') === 'leads' ? 'active' : '' ?>" href="<?= BASE_URL ?>/?route=leads">Leads</a></li>
                <li class="nav-item"><a class="nav-link <?= ($_GET['route'] ?? '') === 'projects' ? 'active' : '' ?>" href="<?= BASE_URL ?>/?route=projects">Dev Pipeline</a></li>
                <li class="nav-item"><a class="nav-link <?= ($_GET['route'] ?? '') === 'settings' ? 'active' : '' ?>" href="<?= BASE_URL ?>/?route=settings">Settings</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3 text-white">
                <span><?= htmlspecialchars($authUser['name'] ?? '') ?> (<?= $authUser['role'] ?>)</span>
                <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>/?route=logout">Logout</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="container-fluid py-4">
