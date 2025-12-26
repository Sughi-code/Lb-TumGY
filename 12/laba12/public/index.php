<?php
// Настройка отображения ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Загрузка переменных окружения из файла .env
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_SERVER[$key] = $value;
        $_ENV[$key] = $value;
    }
}

// Подключение файлов приложения
require __DIR__ . '/../src/api.php';
require __DIR__ . '/../src/database.php'; 
require __DIR__ . '/../src/models.php';

// Автозагрузка через Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Отладочное сообщение
    die("❌ Ошибка: Файл автозагрузчика не найден по пути: " . __DIR__ . '/../vendor/autoload.php');
}

use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

$routeDispatcher = FastRoute\simpleDispatcher(function (RouteCollector $route) {
    // GET
    $route->get('/films/{id:\d+}/details', 'getFilmDetails');
    $route->get('/films', 'getAllFilms');
    $route->get('/films/{id:\d+}', 'getFilmById');
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

if (false !== $questionMarkPosition = strpos($requestUri, '?')) {
    $requestUri = substr($requestUri, 0, $questionMarkPosition);
}

$requestUri = rawurldecode($requestUri);

$routeInfo = $routeDispatcher->dispatch($httpMethod, $requestUri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo json_encode([
            'error' => true,
            'message' => 'Запрашиваемый ресурс не найден',
            'status' => 404
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo json_encode([
            'error' => true,
            'message' => 'Метод не разрешен. Разрешены: ' . implode(', ', $allowedMethods),
            'status' => 405
        ], JSON_UNESCAPED_UNICODE);
        exit;
        
    case Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $routeParameters = $routeInfo[2];
        
        // Проверяем, существует ли функция-обработчик
        if (!function_exists($handler)) {
            http_response_code(500);
            echo json_encode([
                'error' => true,
                'message' => "Функция-обработчик '$handler' не найдена",
                'status' => 500
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Вызываем обработчик с подключением к БД
        call_user_func_array($handler, array_merge([$databaseConnection], array_values($routeParameters)));
        break;
}
?>