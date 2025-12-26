<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'vendor/autoload.php';

use FastRoute\RouteCollector;
use FastRoute\Dispatcher;

require_once 'models/Customer.php';
require_once 'models/Film.php';
require_once 'models/Rental.php';
require_once 'controllers/CustomerController.php';
require_once 'controllers/FilmController.php';
require_once 'controllers/RentalController.php';
require_once 'controllers/FilmDetailsController.php';

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function sendJsonError($message, $statusCode = 404) {
    sendJsonResponse([
        'error' => true,
        'message' => $message,
        'status' => $statusCode
    ], $statusCode);
}

function getJsonBody() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data', 400);
    }
    
    return $data;
}

$connection = getDBConnection();

$customerController = new CustomerController($connection);
$filmController = new FilmController($connection);
$rentalController = new RentalController($connection);
$filmDetailsController = new FilmDetailsController($connection, KINOPOISK_API_KEY);

$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r) use (
    $customerController, 
    $filmController, 
    $rentalController,
    $filmDetailsController,
    $connection
) {
    $r->addRoute('GET', '/customers', function() use ($customerController) {
        try {
            $result = $customerController->getAll();
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/customers/{id:[0-9]+}', function($params) use ($customerController) {
        try {
            $result = $customerController->getById($params['id']);
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('POST', '/customers', function() use ($customerController) {
        try {
            $data = getJsonBody();
            $result = $customerController->create($data);
            
            $customerId = isset($result['data']['customer_id']) ? $result['data']['customer_id'] : null;
            if ($customerId) {
                header('Location: /customers/' . $customerId);
            }
            
            sendJsonResponse($result, 201);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('PUT', '/customers/{id:[0-9]+}', function($params) use ($customerController) {
        try {
            $data = getJsonBody();
            $result = $customerController->update($params['id'], $data);
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('DELETE', '/customers/{id:[0-9]+}', function($params) use ($customerController) {
        try {
            $result = $customerController->delete($params['id']);
            http_response_code(204);
            exit;
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/films', function() use ($filmController) {
        try {
            $result = $filmController->getAll();
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/films/{id:[0-9]+}', function($params) use ($filmController) {
        try {
            $result = $filmController->getById($params['id']);
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('POST', '/films', function() use ($filmController) {
        try {
            $data = getJsonBody();
            $result = $filmController->create($data);
            
            $filmId = isset($result['data']['film_id']) ? $result['data']['film_id'] : null;
            if ($filmId) {
                header('Location: /films/' . $filmId);
            }
            
            sendJsonResponse($result, 201);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('PUT', '/films/{id:[0-9]+}', function($params) use ($filmController) {
        try {
            $data = getJsonBody();
            $result = $filmController->update($params['id'], $data);
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('DELETE', '/films/{id:[0-9]+}', function($params) use ($filmController) {
        try {
            $result = $filmController->delete($params['id']);
            http_response_code(204);
            exit;
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/films/{id:[0-9]+}/details', function($params) use ($filmDetailsController) {
        try {
            $dataParam = $_GET['data'] ?? 'details';
            
            if (is_string($dataParam)) {
                $fields = explode(',', $dataParam);
                $fields = array_map('trim', $fields);
            } else {
                $fields = ['details'];
            }
            
            $allowedFields = ['details', 'reviews', 'persons', 'similar', 'images', 'rating'];
            $validatedFields = array_intersect($fields, $allowedFields);
            
            if (empty($validatedFields)) {
                $validatedFields = ['details'];
            }
            
            $result = $filmDetailsController->getFilmDetails($params['id'], $validatedFields);
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/rentals', function() use ($rentalController) {
        try {
            $result = $rentalController->getAll();
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/rentals/{id:[0-9]+}', function($params) use ($rentalController) {
        try {
            $result = $rentalController->getById($params['id']);
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('POST', '/rentals', function() use ($rentalController) {
        try {
            $data = getJsonBody();
            $result = $rentalController->create($data);
            
            $rentalId = isset($result['data']['rental_id']) ? $result['data']['rental_id'] : null;
            if ($rentalId) {
                header('Location: /rentals/' . $rentalId);
            }
            
            sendJsonResponse($result, 201);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('PUT', '/rentals/{id:[0-9]+}', function($params) use ($rentalController) {
        try {
            $data = getJsonBody();
            $result = $rentalController->update($params['id'], $data);
            sendJsonResponse($result);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('DELETE', '/rentals/{id:[0-9]+}', function($params) use ($rentalController) {
        try {
            $result = $rentalController->delete($params['id']);
            http_response_code(204);
            exit;
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/stores', function() {
        try {
            $pagination = getPaginationParams();
            
            $allowedFilters = [
                'manager_staff_id' => 'equal'
            ];
            
            $where = buildWhereConditions($allowedFilters);
            $orderBy = buildOrderBy(['manager_staff_id']);
            
            $result = getAllRecords('store', '*', implode(' AND ', $where['conditions']), $where['params'], $orderBy, $pagination);
            
            sendJsonResponse([
                'success' => true,
                'data' => $result['data'],
                'pagination' => [
                    'page' => $pagination['page'],
                    'limit' => $pagination['limit'],
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $pagination['limit'])
                ]
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/stores/{id:[0-9]+}', function($params) use ($connection) {
        try {
            $id = $params['id'];
            
            $query = "SELECT * FROM store WHERE store_id = :id";
            $stmt = $connection->prepare($query);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            
            $store = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                throw new Exception("Store not found", 404);
            }
            
            sendJsonResponse([
                'success' => true,
                'data' => $store
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
    
    $r->addRoute('GET', '/customer/{id:[0-9]+}', function($params) use ($connection) {
        try {
            $id = $params['id'];
            
            $customerModel = new Customer($connection);
            $data = $customerModel->getById($id);
            
            sendJsonResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            sendJsonError($e->getMessage(), $code);
        }
    });
});

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$basePath = '';
if (isset($_SERVER['CONTEXT_PREFIX'])) {
    $basePath = $_SERVER['CONTEXT_PREFIX'];
}

if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

if ($uri === '') {
    $uri = '/';
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        sendJsonError('Resource not found', 404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        sendJsonError('Method not allowed', 405);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];
        
        if (is_callable($handler)) {
            $handler($vars);
        } else {
            if (isset($vars['id'])) {
                $handler($vars['id']);
            } else {
                $handler();
            }
        }
        break;
}
?>