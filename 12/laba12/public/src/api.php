<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

function sendJsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function sendJsonError(string $message, int $statusCode, string $detail = ''): void
{
    $response = [
        'error' => true,
        'message' => $message,
        'status' => $statusCode
    ];
    if ($detail) {
        $response['detail'] = $detail;
    }
    sendJsonResponse($response, $statusCode);
}

function getRequestBody(): array
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonError('Некорректный JSON в теле запроса', 400, json_last_error_msg());
    }
    return $data ?: [];
}

function getAllFilms(PDO $databaseConnection): void
{
    try {
        $builder = new QueryBuilder(
            $databaseConnection,
            'film',
            ['film_id', 'title', 'description', 'release_year', 'language_id', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating'],
            'film_id'
        );
        $result = $builder->execute();
        sendJsonResponse($result);
    } catch (Exception $e) {
        sendJsonError('Ошибка получения фильмов', 500, $e->getMessage());
    }
}

function getFilmById(PDO $databaseConnection, string $filmId): void
{
    try {
        $builder = new QueryBuilder(
            $databaseConnection,
            'film',
            ['film_id', 'title', 'description', 'release_year', 'language_id', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating', 'special_features', 'last_update'],
            'film_id'
        );
        $result = $builder->executeSingle('film_id', $filmId);
        if (!$result) {
            sendJsonError('Запись не найдена', 404, "Фильм с ID=$filmId не существует");
        }
        sendJsonResponse(['success' => true, 'data' => $result]);
    } catch (Exception $e) {
        sendJsonError('Ошибка получения фильма по ID', 500, $e->getMessage());
    }
}

function kinopoiskApiRequest(string $endpoint, array $params = [], string $apiKey = ''): ?array
{
    $baseUrl = 'https://api.kinopoisk.dev/v1.4';
    $url = $baseUrl . $endpoint;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-API-KEY: {$apiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("cURL error: " . $error);
        return null;
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("API request failed with HTTP code: " . $httpCode);
        return null;
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        return null;
    }
    
    return $data ?: null;
}

function getKinopoiskFilmDetails(string $kpId, array $fields, string $apiKey): ?array
{
    $result = [];
    
    if (in_array('details', $fields) || in_array('persons', $fields) || in_array('similar', $fields) || in_array('rating', $fields)) {
        $filmData = kinopoiskApiRequest("/movie/{$kpId}", [], $apiKey);
        if ($filmData) {
            if (in_array('details', $fields)) {
                $result['details'] = [
                    'id' => $filmData['id'] ?? null,
                    'name' => $filmData['name'] ?? null,
                    'description' => $filmData['description'] ?? null,
                    'year' => $filmData['year'] ?? null,
                    'genres' => array_map(function($genre) {
                        return $genre['name'] ?? null;
                    }, $filmData['genres'] ?? []),
                    'countries' => array_map(function($country) {
                        return $country['name'] ?? null;
                    }, $filmData['countries'] ?? [])
                ];
            }
            
            if (in_array('persons', $fields)) {
                $result['persons'] = array_map(function($person) {
                    return [
                        'id' => $person['id'] ?? null,
                        'name' => $person['name'] ?? null,
                        'enName' => $person['enName'] ?? null,
                        'role' => $person['profession'] ?? null
                    ];
                }, $filmData['persons'] ?? []);
            }
            
            if (in_array('similar', $fields)) {
                $result['similar'] = array_map(function($movie) {
                    return [
                        'id' => $movie['id'] ?? null,
                        'name' => $movie['name'] ?? null,
                        'year' => $movie['year'] ?? null
                    ];
                }, $filmData['similarMovies'] ?? []);
            }
            
            if (in_array('rating', $fields)) {
                $result['rating'] = [
                    'kp' => $filmData['rating']['kp'] ?? null,
                    'imdb' => $filmData['rating']['imdb'] ?? null,
                    'filmCritics' => $filmData['rating']['filmCritics'] ?? null
                ];
            }
        }
    }
    
    if (in_array('reviews', $fields)) {
        $reviewsData = kinopoiskApiRequest("/review", [
            'movieId' => $kpId,
            'type' => 'critic'
        ], $apiKey);
        
        if ($reviewsData && isset($reviewsData['docs'])) {
            $result['reviews'] = array_map(function($review) {
                return [
                    'id' => $review['id'] ?? null,
                    'title' => $review['title'] ?? null,
                    'review' => $review['review'] ?? null,
                    'author' => $review['author'] ?? 'Неизвестный автор'
                ];
            }, $reviewsData['docs']);
        }
    }
    
    if (in_array('images', $fields)) {
        $imagesData = kinopoiskApiRequest("/image", [
            'movieId' => $kpId,
            'type' => 'cover'
        ], $apiKey);
        
        if ($imagesData && isset($imagesData['docs'])) {
            $result['images'] = array_map(function($image) {
                return [
                    'url' => $image['url'] ?? null,
                    'type' => $image['type'] ?? null
                ];
            }, $imagesData['docs']);
        }
    }
    
    return $result;
}

function getFilmDetails(PDO $databaseConnection, string $filmId): void
{
    try {
        $apiKey = getenv('KINOPOISK_API_KEY') ?: ($_SERVER['KINOPOISK_API_KEY'] ?? '');
        
        if (empty($apiKey)) {
            sendJsonError('API ключ Кинопоиска не настроен', 500, 'Пожалуйста, установите API ключ в файле .env');
        }
        
        $fieldsParam = $_GET['fields'] ?? '';
        $requestedFields = is_array($fieldsParam) ? $fieldsParam : explode(',', $fieldsParam);
        $requestedFields = array_map('trim', $requestedFields);
        
        $validFields = ['details', 'reviews', 'persons', 'similar', 'images', 'rating'];
        $requestedFields = array_intersect($requestedFields, $validFields);
        
        if (empty($requestedFields)) {
            sendJsonError('Не указаны поля для запроса', 400, 'Укажите параметр fields со списком полей через запятую. Доступные поля: ' . implode(', ', $validFields));
        }
        
        $builder = new QueryBuilder(
            $databaseConnection,
            'film',
            ['film_id', 'title', 'description', 'release_year', 'language_id', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating'],
            'film_id'
        );
        $result = $builder->executeSingle('film_id', $filmId);
        
        if (!$result) {
            sendJsonError('Запись не найдена', 404, "Фильм с ID=$filmId не существует");
        }
        
        $searchResult = kinopoiskApiRequest('/movie/search', [
            'query' => trim($result['title']),
            'limit' => 1
        ], $apiKey);
        
        if (!$searchResult || !isset($searchResult['docs']) || empty($searchResult['docs'])) {
            sendJsonError('Фильм не найден в Кинопоиске', 404, "Фильм '{$result['title']}' не найден в базе Кинопоиска");
        }
        
        $kpFilmId = $searchResult['docs'][0]['id'];
        $kinopoiskDetails = getKinopoiskFilmDetails((string)$kpFilmId, $requestedFields, $apiKey);
        
        if (!$kinopoiskDetails) {
            sendJsonError('Ошибка при получении данных из API Кинопоиска', 502, 'Не удалось получить данные от стороннего сервиса');
        }
        
        $response = [
            'success' => true,
            'data' => [
                'film_id' => $result['film_id'],
                'title' => $result['title'],
                'rental_duration' => $result['rental_duration'],
                'rental_rate' => (float)$result['rental_rate'],
                'replacement_cost' => (float)$result['replacement_cost'],
                'kinopoisk' => $kinopoiskDetails
            ]
        ];
        
        sendJsonResponse($response);
    } catch (Exception $e) {
        sendJsonError('Ошибка получения деталей фильма', 500, $e->getMessage());
    }
}

class QueryBuilder
{
    private array $filters = [];
    private array $params = [];
    private string $orderBy = '';
    private string $orderDirection = 'ASC';
    private int $limit = 10;
    private int $offset = 0;
    
    public function __construct(
        private PDO $databaseConnection,
        private string $tableName,
        private array $selectFields,
        private string $defaultSortField = 'id'
    ) {
        $this->applyPagination();
        $this->applySorting();
    }
    
    private function applyPagination(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $this->limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
        $this->offset = ($page - 1) * $this->limit;
    }
    
    private function applySorting(): void
    {
        $allowedFields = [
            'stores' => ['store_id', 'manager_staff_id', 'address_id'],
            'customers' => ['customer_id', 'first_name', 'last_name', 'create_date'],
            'films' => ['film_id', 'title', 'release_year', 'length', 'rating', 'rental_rate'],
            'rentals' => ['rental_id', 'rental_date', 'return_date', 'inventory_id', 'customer_id', 'staff_id']
        ];
        
        $allowedSortFields = $allowedFields[$this->tableName] ?? [$this->defaultSortField];
        
        if (isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields)) {
            $this->orderBy = $_GET['sort'];
        } else {
            $this->orderBy = $this->defaultSortField;
        }
        
        if (isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'])) {
            $this->orderDirection = strtoupper($_GET['order']);
        }
    }
    
    public function execute(): array
    {
        $whereClause = !empty($this->filters) ? 'WHERE ' . implode(' AND ', $this->filters) : '';
        $selectFields = implode(', ', $this->selectFields);
        
        $countQuery = "SELECT COUNT(*) as total FROM {$this->tableName} $whereClause";
        $countStatement = $this->databaseConnection->prepare($countQuery);
        $countStatement->execute($this->params);
        $countResult = $countStatement->fetch();
        $totalItems = (int)$countResult['total'];
        $totalPages = ceil($totalItems / $this->limit);
        
        $dataQuery = "
            SELECT $selectFields 
            FROM {$this->tableName} 
            $whereClause 
            ORDER BY {$this->orderBy} {$this->orderDirection} 
            LIMIT :limit OFFSET :offset
        ";
        
        $statement = $this->databaseConnection->prepare($dataQuery);
        foreach ($this->params as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
        $statement->bindValue(':limit', $this->limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $this->offset, PDO::PARAM_INT);
        
        $statement->execute();
        $data = $statement->fetchAll();
        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'currentPage' => ($this->offset / $this->limit) + 1,
                'itemsPerPage' => $this->limit,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages
            ]
        ];
    }
    
    public function executeSingle(string $idField, string $idValue): ?array
    {
        $selectFields = implode(', ', $this->selectFields);
        $query = "SELECT $selectFields FROM {$this->tableName} WHERE $idField = :id";
        
        $statement = $this->databaseConnection->prepare($query);
        $statement->bindValue(':id', $idValue);
        $statement->execute();
        $result = $statement->fetch();
        return $result === false ? null : $result;
    }
}
?>