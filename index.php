<?php
date_default_timezone_set('Europe/Moscow');
session_start();

require_once 'service/DBConnect.php';
require_once 'middleware/CorsMiddleware.php';
require_once 'Router.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $mysqli = getDBConnection();
} catch (Exception $e) {
    exit();
}

$router = new Router($mysqli);

$router->addRoute('POST', 'registr', 'user', 'registration', null);
$router->addRoute('POST', 'auth', 'user', 'authorization', null);
$router->addRoute('GET', 'courses', 'courses', 'coursesHandler', 'auth');
$router->addRoute('POST', 'courses/buy', 'courses', 'orderCourse', 'auth');
$router->addRoute('POST', 'payment-webhook', 'orders', 'payment', 'auth');
$router->addRoute('GET', 'orders', 'orders', 'deleteOrder', 'auth');

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
