<?php
define('DB_HOST', 'MySQL-8.0');
define('DB_USER', 'root'); 
define('DB_PASS', '');
define('DB_NAME', 'sakila');
define('KINOPOISK_API_KEY', 'PW0WYZQ-7VTMC6Q-G1K5K69-11YM3C3');

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $connection = new PDO($dsn, DB_USER, DB_PASS);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        require_once 'models/Migration.php';
        $migration = new Migration($connection);
        $migrationResult = $migration->checkAndRunMigrations();
        
        if (isset($migrationResult['created_tables']) && !empty($migrationResult['created_tables'])) {
            error_log("Migration completed. Created tables: " . implode(', ', $migrationResult['created_tables']));
        }
        
        return $connection;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Database connection failed: ' . $e->getMessage(),
            'status' => 500
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function getPaginationParams() {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

function buildWhereConditions($allowedFilters) {
    $conditions = [];
    $params = [];
    
    foreach ($allowedFilters as $filter => $type) {
        if (isset($_GET[$filter])) {
            $value = $_GET[$filter];
            
            switch ($type) {
                case 'equal':
                    $conditions[] = "$filter = :$filter";
                    $params[$filter] = $value;
                    break;
                    
                case 'multiple':
                    $values = is_array($value) ? $value : explode(',', $value);
                    $placeholders = [];
                    foreach ($values as $index => $val) {
                        $paramName = $filter . '_' . $index;
                        $placeholders[] = ":$paramName";
                        $params[$paramName] = $val;
                    }
                    $conditions[] = "$filter IN (" . implode(',', $placeholders) . ")";
                    break;
                    
                case 'range':
                    if (is_numeric($value)) {
                        $conditions[] = "$filter = :$filter";
                        $params[$filter] = (int)$value;
                    } elseif (str_starts_with($value, '>') && is_numeric(substr($value, 1))) {
                        $numValue = (int)substr($value, 1);
                        $conditions[] = "$filter > :$filter";
                        $params[$filter] = $numValue;
                    } elseif (str_starts_with($value, '<') && is_numeric(substr($value, 1))) {
                        $numValue = (int)substr($value, 1);
                        $conditions[] = "$filter < :$filter";
                        $params[$filter] = $numValue;
                    } else {
                        $rangeParts = explode('<', $value);
                        if (count($rangeParts) === 2 && is_numeric($rangeParts[0]) && is_numeric($rangeParts[1])) {
                            $conditions[] = "$filter BETWEEN :{$filter}_min AND :{$filter}_max";
                            $params[$filter . '_min'] = (int)$rangeParts[0];
                            $params[$filter . '_max'] = (int)$rangeParts[1];
                        }
                    }
                    break;
                    
                case 'date_range':
                    if (str_starts_with($value, '>')) {
                        $dateValue = substr($value, 1);
                        if (isValidDate($dateValue)) {
                            $conditions[] = "$filter > :$filter";
                            $params[$filter] = $dateValue;
                        }
                    } elseif (str_starts_with($value, '<')) {
                        $dateValue = substr($value, 1);
                        if (isValidDate($dateValue)) {
                            $conditions[] = "$filter < :$filter";
                            $params[$filter] = $dateValue;
                        }
                    } else {
                        $rangeParts = explode('<', $value);
                        if (count($rangeParts) === 2 && isValidDate($rangeParts[0]) && isValidDate($rangeParts[1])) {
                            $conditions[] = "$filter BETWEEN :{$filter}_min AND :{$filter}_max";
                            $params[$filter . '_min'] = $rangeParts[0];
                            $params[$filter . '_max'] = $rangeParts[1];
                        } elseif (isValidDate($value)) {
                            $conditions[] = "$filter = :$filter";
                            $params[$filter] = $value;
                        }
                    }
                    break;
            }
        }
    }
    
    return [
        'conditions' => $conditions,
        'params' => $params
    ];
}

function isValidDate($date) {
    if (empty($date)) {
        return false;
    }
    
    if (strlen($date) === 10 && $date[4] === '-' && $date[7] === '-') {
        $parts = explode('-', $date);
        if (count($parts) === 3) {
            $year = (int)$parts[0];
            $month = (int)$parts[1];
            $day = (int)$parts[2];
            
            return checkdate($month, $day, $year);
        }
    }
    
    return false;
}

function buildOrderBy($allowedSortFields, $defaultSort = null) {
    $sort = isset($_GET['sort']) ? $_GET['sort'] : $defaultSort;
    
    if ($sort && in_array($sort, $allowedSortFields)) {
        $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';
        return "ORDER BY $sort $order";
    }
    
    return $defaultSort ? "ORDER BY $defaultSort ASC" : "";
}

function getAllRecords($tableName, $fields = '*', $whereConditions = '', $params = [], $orderBy = '', $pagination = null) {
    $connection = getDBConnection();
    
    $query = "SELECT $fields FROM `$tableName`";
    
    if ($whereConditions) {
        $query .= " WHERE $whereConditions";
    }
    
    if ($orderBy) {
        $query .= " $orderBy";
    }
    
    if ($pagination) {
        $query .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = $pagination['limit'];
        $params['offset'] = $pagination['offset'];
    }
    
    try {
        $stmt = $connection->prepare($query);
        
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$key", $value, $paramType);
        }
        
        $stmt->execute();
        $records = $stmt->fetchAll();
        
        $countQuery = "SELECT COUNT(*) as total FROM `$tableName`";
        if ($whereConditions) {
            $countQuery .= " WHERE $whereConditions";
        }
        
        $countStmt = $connection->prepare($countQuery);
        foreach ($params as $key => $value) {
            if ($key !== 'limit' && $key !== 'offset') {
                $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $countStmt->bindValue(":$key", $value, $paramType);
            }
        }
        $countStmt->execute();
        $totalCount = $countStmt->fetchColumn();
        
        return [
            'data' => $records,
            'total' => $totalCount
        ];
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error executing query: ' . $e->getMessage(),
            'status' => 500
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function getRecordById($tableName, $id, $idField = null) {
    $connection = getDBConnection();
    
    if ($idField === null) {
        $idField = $tableName . '_id';
    }
    
    $id = intval($id);
    
    $query = "SELECT * FROM `$tableName` WHERE `$idField` = :id";
    
    try {
        $stmt = $connection->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $record = $stmt->fetch();
        
        if (!$record) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode([
                'error' => true,
                'message' => "Record not found in table $tableName with ID $id",
                'status' => 404
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        return $record;
        
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => true,
            'message' => 'Error executing query: ' . $e->getMessage(),
            'status' => 500
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>