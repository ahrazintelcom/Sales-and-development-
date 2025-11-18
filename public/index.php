<?php
session_start();

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if ($baseUrl === '' || $baseUrl === '.') {
    $baseUrl = '';
}
define('BASE_URL', $baseUrl);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Controllers\AuthController;
use App\Controllers\LeadController;
use App\Controllers\ProjectController;
use App\Controllers\SettingsController;
use App\Core\Auth;

$route = $_GET['route'] ?? null;
if ($route === null) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if ($requestPath !== null && BASE_URL !== '' && str_starts_with($requestPath, BASE_URL)) {
        $requestPath = substr($requestPath, strlen(BASE_URL));
    }
    $requestPath = trim($requestPath ?? '', '/');
    if ($requestPath !== '' && $requestPath !== 'index.php') {
        $route = $requestPath;
    }
}
$route = $route ?: 'leads';
$method = $_SERVER['REQUEST_METHOD'];

if (!Auth::check() && !in_array($route, ['login', 'login_post'])) {
    $route = 'login';
}

switch ($route) {
    case 'login':
        (new AuthController())->showLogin();
        break;
    case 'login_post':
        (new AuthController())->login();
        break;
    case 'logout':
        (new AuthController())->logout();
        break;
    case 'leads':
        (new LeadController())->index();
        break;
    case 'leads/import':
        (new LeadController())->importCsv();
        break;
    case 'leads/view':
        (new LeadController())->show();
        break;
    case 'leads/status':
        (new LeadController())->markStatus();
        break;
    case 'leads/regenerate':
        (new LeadController())->regenerateScripts();
        break;
    case 'leads/proposal':
        (new LeadController())->generateProposal();
        break;
    case 'projects':
        (new ProjectController())->index();
        break;
    case 'projects/view':
        (new ProjectController())->show();
        break;
    case 'projects/task':
        (new ProjectController())->addTask();
        break;
    case 'projects/spec':
        (new ProjectController())->regenerateSpec();
        break;
    case 'projects/github':
        (new ProjectController())->createGithub();
        break;
    case 'settings':
        (new SettingsController())->index();
        break;
    case 'settings/save':
        (new SettingsController())->save();
        break;
    case 'settings/pricing':
        (new SettingsController())->savePricing();
        break;
    case 'settings/multiplier':
        (new SettingsController())->saveMultiplier();
        break;
    default:
        http_response_code(404);
        echo 'Not Found';
}
