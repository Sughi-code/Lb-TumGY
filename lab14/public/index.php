<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/src/database.php';
require_once __DIR__ . '/src/models.php';
require_once __DIR__ . '/src/controllers.php';
require_once __DIR__ . '/src/views.php';

use FastRoute\RouteCollector;

// Настройка маршрутов
$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r) {
    $r->addRoute('GET', '/', ['RentalController', 'index']);
    $r->addRoute('GET', '/add-tenant', ['RentalController', 'showAddTenantForm']);
    $r->addRoute('POST', '/add-tenant', ['RentalController', 'addTenant']);
    $r->addRoute('GET', '/add-property', ['RentalController', 'showAddPropertyForm']);
    $r->addRoute('POST', '/add-property', ['RentalController', 'addProperty']);
    $r->addRoute('GET', '/add-rental-info', ['RentalController', 'showAddRentalInfoForm']);
    $r->addRoute('POST', '/add-rental-info', ['RentalController', 'addRentalInfo']);
    $r->addRoute('POST', '/delete-tenant/{id}', ['RentalController', 'deleteTenant']);
    $r->addRoute('GET', '/delete-tenant/{id}', ['RentalController', 'deleteTenant']);
    $r->addRoute('POST', '/delete-property/{id}', ['RentalController', 'deleteProperty']);
    $r->addRoute('GET', '/delete-property/{id}', ['RentalController', 'deleteProperty']);
    $r->addRoute('POST', '/delete-rental-info/{id}', ['RentalController', 'deleteRentalInfo']);
    $r->addRoute('GET', '/delete-rental-info/{id}', ['RentalController', 'deleteRentalInfo']);
    
    // Маршруты для отчетов
    $r->addRoute('GET', '/report/property-type', ['RentalController', 'showPropertyTypeReport']);
    $r->addRoute('GET', '/report/tenants-for-properties', ['RentalController', 'showTenantsForPropertiesReport']);
    $r->addRoute('GET', '/report/properties-never-rented', ['RentalController', 'showPropertiesNeverRentedReport']);
    $r->addRoute('GET', '/report/properties-rented-more-than-3-times', ['RentalController', 'showPropertiesRentedMoreThan3TimesReport']);
    $r->addRoute('GET', '/report/properties-rented-more-than-2-times-with-long-term', ['RentalController', 'showPropertiesRentedMoreThan2TimesWithLongTermReport']);
    $r->addRoute('GET', '/report/properties-with-rental-stats', ['RentalController', 'showPropertiesWithRentalStatsReport']);
    $r->addRoute('GET', '/report/tenants-with-rental-stats', ['RentalController', 'showTenantsWithRentalStatsReport']);
    $r->addRoute('GET', '/report/rented-properties-by-type-and-quarter', ['RentalController', 'showRentedPropertiesByTypeAndQuarterReport']);
    $r->addRoute('GET', '/report/tenants-with-different-properties', ['RentalController', 'showTenantsWithDifferentPropertiesReport']);
    $r->addRoute('GET', '/report/adjusted-rents', ['RentalController', 'showAdjustedRentsReport']);
});

// Определение URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Убираем query string из URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

// Выбор обработчика маршрута
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // Маршрут не найден
        $controller = new RentalController(getDBConnection());
        $html = renderLayout('404', ['message' => 'Страница не найдена']);
        echo $html;
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        // Метод не разрешен
        $allowedMethods = $routeInfo[1];
        $controller = new RentalController(getDBConnection());
        $html = renderLayout('404', ['message' => 'Метод не разрешен']);
        echo $html;
        break;
    case FastRoute\Dispatcher::FOUND:
        // Маршрут найден
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        
        $controller = new RentalController(getDBConnection());
        
        if (is_array($handler) && count($handler) === 2) {
            $controllerName = $handler[0];
            $methodName = $handler[1];
            
            if (class_exists($controllerName) && method_exists($controller, $methodName)) {
                $controller->$methodName(...array_values($vars));
            } else {
                $html = renderLayout('404', ['message' => 'Обработчик не найден']);
                echo $html;
            }
        } else {
            $html = renderLayout('404', ['message' => 'Неверный обработчик маршрута']);
            echo $html;
        }
        break;
}
