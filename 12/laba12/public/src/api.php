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

function getFilmDetails(PDO $databaseConnection, string $id): void
{
    try {
        $apiKey = 'PW0WYZQ-7VTMC6Q-G1K5K69-11YM3C3'; // Используем фиксированный API ключ
        
        $type = $_GET['type'] ?? '';
        $validTypes = ['details', 'reviews', 'persons', 'similar', 'images', 'rating'];
        
        if (!in_array($type, $validTypes)) {
            sendJsonError('Неверный тип запроса', 400, 'Допустимые значения: ' . implode(', ', $validTypes));
            return;
        }
        
        $builder = new QueryBuilder(
            $databaseConnection,
            'film',
            ['film_id', 'title', 'description', 'release_year', 'language_id', 'rental_duration', 'rental_rate', 'length', 'replacement_cost', 'rating'],
            'film_id'
        );
        $result = $builder->executeSingle('film_id', $id);
        
        if (!$result) {
            sendJsonError('Запись не найдена', 404, "Фильм с ID=$id не существует");
            return;
        }
        
        // Добавляем данные из базы данных в ответ
        $response = [
            'rental_duration' => $result['rental_duration'],
            'rental_rate' => (float)$result['rental_rate'],
            'replacement_cost' => (float)$result['replacement_cost']
        ];
        
        // Поиск фильма в Кинопоиске по названию
        $searchUrl = 'https://api.kinopoisk.dev/v1.4/movie/search';
        $searchParams = [
            'query' => trim($result['title'])
        ];
        
        $searchResult = kinopoiskApiRequest('/movie/search', $searchParams, $apiKey);
        
        if (!$searchResult || !isset($searchResult['docs']) || empty($searchResult['docs'])) {
            // Если фильм не найден в Кинопоиске, возвращаем только данные из базы
            $response['message'] = "Фильм '{$result['title']}' не найден в базе Кинопоиска, возвращены только данные из базы данных";
            sendJsonResponse($response);
            return;
        }
        
        $kpFilmId = $searchResult['docs'][0]['id'];
        
        // Запрашиваем конкретные данные в зависимости от типа
        $baseUrl = 'https://api.kinopoisk.dev/v1.4';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-API-KEY: {$apiKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        switch ($type) {
            case 'details':
                $url = "{$baseUrl}/movie/{$kpFilmId}?token={$apiKey}";
                curl_setopt($ch, CURLOPT_URL, $url);
                $apiResponse = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    error_log("cURL error: " . $error);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, $error);
                    return;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("API request failed with HTTP code: " . $httpCode);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, "HTTP код: {$httpCode}");
                    return;
                }
                
                $data = json_decode($apiResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    sendJsonError('Ошибка при разборе ответа от API Кинопоиска', 502, json_last_error_msg());
                    return;
                }
                
                $response['details'] = $data;
                break;
                
            case 'reviews':
                $url = "{$baseUrl}/review?movieId={$kpFilmId}&token={$apiKey}";
                curl_setopt($ch, CURLOPT_URL, $url);
                $apiResponse = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    error_log("cURL error: " . $error);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, $error);
                    return;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("API request failed with HTTP code: " . $httpCode);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, "HTTP код: {$httpCode}");
                    return;
                }
                
                $data = json_decode($apiResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    sendJsonError('Ошибка при разборе ответа от API Кинопоиска', 502, json_last_error_msg());
                    return;
                }
                
                $response['reviews'] = $data['docs'] ?? [];
                break;
                
            case 'persons':
                $url = "{$baseUrl}/person?movieId={$kpFilmId}&token={$apiKey}";
                curl_setopt($ch, CURLOPT_URL, $url);
                $apiResponse = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    error_log("cURL error: " . $error);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, $error);
                    return;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("API request failed with HTTP code: " . $httpCode);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, "HTTP код: {$httpCode}");
                    return;
                }
                
                $data = json_decode($apiResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    sendJsonError('Ошибка при разборе ответа от API Кинопоиска', 502, json_last_error_msg());
                    return;
                }
                
                $response['persons'] = $data['docs'] ?? [];
                break;
                
            case 'similar':
                $url = "{$baseUrl}/movie/similar?movieId={$kpFilmId}&token={$apiKey}";
                curl_setopt($ch, CURLOPT_URL, $url);
                $apiResponse = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    error_log("cURL error: " . $error);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, $error);
                    return;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("API request failed with HTTP code: " . $httpCode);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, "HTTP код: {$httpCode}");
                    return;
                }
                
                $data = json_decode($apiResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    sendJsonError('Ошибка при разборе ответа от API Кинопоиска', 502, json_last_error_msg());
                    return;
                }
                
                $response['similar'] = $data['docs'] ?? [];
                break;
                
            case 'images':
                $url = "{$baseUrl}/image?movieId={$kpFilmId}&type=cover&token={$apiKey}";
                curl_setopt($ch, CURLOPT_URL, $url);
                $apiResponse = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    error_log("cURL error: " . $error);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, $error);
                    return;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("API request failed with HTTP code: " . $httpCode);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, "HTTP код: {$httpCode}");
                    return;
                }
                
                $data = json_decode($apiResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    sendJsonError('Ошибка при разборе ответа от API Кинопоиска', 502, json_last_error_msg());
                    return;
                }
                
                $response['images'] = $data['docs'] ?? [];
                break;
                
            case 'rating':
                $url = "{$baseUrl}/movie/{$kpFilmId}?token={$apiKey}";
                curl_setopt($ch, CURLOPT_URL, $url);
                $apiResponse = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    error_log("cURL error: " . $error);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, $error);
                    return;
                }
                
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    error_log("API request failed with HTTP code: " . $httpCode);
                    sendJsonError('Ошибка при запросе к API Кинопоиска', 502, "HTTP код: {$httpCode}");
                    return;
                }
                
                $data = json_decode($apiResponse, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error: " . json_last_error_msg());
                    sendJsonError('Ошибка при разборе ответа от API Кинопоиска', 502, json_last_error_msg());
                    return;
                }
                
                $response['rating'] = $data['rating'] ?? null;
                break;
        }
        
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