<?php
class Film {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll($filters = [], $sort = null, $order = 'ASC', $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if ($field === 'title' && !empty($value)) {
                    $whereConditions[] = "title LIKE :title";
                    $params['title'] = '%' . $value . '%';
                } elseif ($field === 'release_year') {
                    if (is_numeric($value)) {
                        $whereConditions[] = "release_year = :release_year";
                        $params['release_year'] = (int)$value;
                    } elseif (str_starts_with($value, '>') && is_numeric(substr($value, 1))) {
                        $numValue = (int)substr($value, 1);
                        $whereConditions[] = "release_year > :release_year_gt";
                        $params['release_year_gt'] = $numValue;
                    } elseif (str_starts_with($value, '<') && is_numeric(substr($value, 1))) {
                        $numValue = (int)substr($value, 1);
                        $whereConditions[] = "release_year < :release_year_lt";
                        $params['release_year_lt'] = $numValue;
                    } else {
                        $rangeParts = explode('<', $value);
                        if (count($rangeParts) === 2 && is_numeric($rangeParts[0]) && is_numeric($rangeParts[1])) {
                            $whereConditions[] = "release_year BETWEEN :release_year_min AND :release_year_max";
                            $params['release_year_min'] = (int)$rangeParts[0];
                            $params['release_year_max'] = (int)$rangeParts[1];
                        }
                    }
                } elseif ($field === 'language_id') {
                    $values = is_array($value) ? $value : explode(',', $value);
                    $placeholders = [];
                    foreach ($values as $index => $val) {
                        $paramName = 'language_id_' . $index;
                        $placeholders[] = ":$paramName";
                        $params[$paramName] = (int)$val;
                    }
                    $whereConditions[] = "language_id IN (" . implode(',', $placeholders) . ")";
                } elseif ($field === 'original_language_id' && is_numeric($value)) {
                    $whereConditions[] = "original_language_id = :original_language_id";
                    $params['original_language_id'] = (int)$value;
                } elseif ($field === 'length') {
                    if (is_numeric($value)) {
                        $whereConditions[] = "length = :length";
                        $params['length'] = (int)$value;
                    } elseif (str_starts_with($value, '>') && is_numeric(substr($value, 1))) {
                        $numValue = (int)substr($value, 1);
                        $whereConditions[] = "length > :length_gt";
                        $params['length_gt'] = $numValue;
                    } elseif (str_starts_with($value, '<') && is_numeric(substr($value, 1))) {
                        $numValue = (int)substr($value, 1);
                        $whereConditions[] = "length < :length_lt";
                        $params['length_lt'] = $numValue;
                    }
                } elseif ($field === 'rating') {
                    $values = is_array($value) ? $value : explode(',', $value);
                    $validRatings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
                    $filteredValues = array_intersect($values, $validRatings);
                    if (!empty($filteredValues)) {
                        $placeholders = [];
                        foreach ($filteredValues as $index => $val) {
                            $paramName = 'rating_' . $index;
                            $placeholders[] = ":$paramName";
                            $params[$paramName] = $val;
                        }
                        $whereConditions[] = "rating IN (" . implode(',', $placeholders) . ")";
                    }
                }
            }
        }
        
        $query = "SELECT film_id, title, description, release_year, language_id, 
                         rental_duration, rental_rate, length, replacement_cost, 
                         rating, special_features 
                  FROM film";
        
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $allowedSort = ['release_year', 'title', 'length', 'rating', 'rental_rate', 'film_id'];
        if ($sort && in_array($sort, $allowedSort)) {
            $query .= " ORDER BY $sort $order";
        } else {
            $query .= " ORDER BY film_id ASC";
        }
        
        $query .= " LIMIT :limit OFFSET :offset";
        $params['limit'] = (int)$limit;
        $params['offset'] = (int)$offset;
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function getById($id) {
        $query = "SELECT * FROM film WHERE film_id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("Film not found", 404);
            }
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function create($data) {
        $required = ['title', 'language_id'];
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Field '$field' is required";
            }
        }
        
        $validRatings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
        if (isset($data['rating']) && !in_array($data['rating'], $validRatings)) {
            $errors[] = "Invalid rating. Must be one of: " . implode(', ', $validRatings);
        }
        
        if (isset($data['release_year']) && (!is_numeric($data['release_year']) || $data['release_year'] < 1901 || $data['release_year'] > 2155)) {
            $errors[] = "Invalid release year (must be between 1901 and 2155)";
        }
        
        if (isset($data['language_id']) && !is_numeric($data['language_id'])) {
            $errors[] = "Invalid language_id";
        }
        
        if (isset($data['rental_duration']) && (!is_numeric($data['rental_duration']) || $data['rental_duration'] <= 0)) {
            $errors[] = "Invalid rental_duration";
        }
        
        if (isset($data['rental_rate']) && (!is_numeric($data['rental_rate']) || $data['rental_rate'] < 0)) {
            $errors[] = "Invalid rental_rate";
        }
        
        if (isset($data['replacement_cost']) && (!is_numeric($data['replacement_cost']) || $data['replacement_cost'] < 0)) {
            $errors[] = "Invalid replacement_cost";
        }
        
        if (isset($data['length']) && (!is_numeric($data['length']) || $data['length'] < 0)) {
            $errors[] = "Invalid length";
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors), 400);
        }
        
        $fields = ['title', 'description', 'release_year', 'language_id', 
                  'original_language_id', 'rental_duration', 'rental_rate', 
                  'length', 'replacement_cost', 'rating', 'special_features', 
                  'last_update'];
        
        $values = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[] = ":$field";
                $params[$field] = $data[$field];
            } else {
                if ($field === 'rental_duration') {
                    $values[] = ":rental_duration";
                    $params['rental_duration'] = 3;
                } elseif ($field === 'rental_rate') {
                    $values[] = ":rental_rate";
                    $params['rental_rate'] = 4.99;
                } elseif ($field === 'replacement_cost') {
                    $values[] = ":replacement_cost";
                    $params['replacement_cost'] = 19.99;
                } elseif ($field === 'last_update') {
                    $values[] = ":last_update";
                    $params['last_update'] = date('Y-m-d H:i:s');
                } else {
                    $values[] = "NULL";
                }
            }
        }
        
        $query = "INSERT INTO film (" . implode(', ', $fields) . ") 
                 VALUES (" . implode(', ', $values) . ")";
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            
            $lastId = $this->conn->lastInsertId();
            return $this->getById($lastId);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function update($id, $data) {
        $film = $this->getById($id);
        
        $updates = [];
        $params = ['id' => (int)$id];
        
        $allowed = ['title', 'description', 'release_year', 'language_id', 
                   'original_language_id', 'rental_duration', 'rental_rate', 
                   'length', 'replacement_cost', 'rating', 'special_features'];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                if ($field === 'rating') {
                    $validRatings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
                    if (!in_array($data[$field], $validRatings)) {
                        throw new Exception("Invalid rating", 400);
                    }
                }
                
                if (in_array($field, ['release_year', 'language_id', 'original_language_id', 
                    'rental_duration', 'length']) && !is_numeric($data[$field])) {
                    throw new Exception("Invalid value for $field", 400);
                }
                
                if (in_array($field, ['rental_rate', 'replacement_cost']) && 
                    (!is_numeric($data[$field]) || $data[$field] < 0)) {
                    throw new Exception("Invalid value for $field", 400);
                }
                
                if ($field === 'title' && strlen($data[$field]) > 255) {
                    throw new Exception("Title is too long (max 255 characters)", 400);
                }
                
                if ($field === 'description' && strlen($data[$field]) > 65535) {
                    throw new Exception("Description is too long", 400);
                }
                
                $updates[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            throw new Exception("No data provided for update", 400);
        }
        
        $params['last_update'] = date('Y-m-d H:i:s');
        $updates[] = "last_update = :last_update";
        
        $query = "UPDATE film SET " . implode(', ', $updates) . " WHERE film_id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            
            return $this->getById($id);
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function delete($id) {
        $film = $this->getById($id);
        
        $query = "DELETE FROM film WHERE film_id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function getTotalCount($filters = []) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters)) {
            foreach ($filters as $field => $value) {
                if ($field === 'title' && !empty($value)) {
                    $whereConditions[] = "title LIKE :title";
                    $params['title'] = '%' . $value . '%';
                } elseif ($field === 'release_year') {
                    if (is_numeric($value)) {
                        $whereConditions[] = "release_year = :release_year";
                        $params['release_year'] = (int)$value;
                    }
                } elseif ($field === 'language_id') {
                    $values = is_array($value) ? $value : explode(',', $value);
                    $placeholders = [];
                    foreach ($values as $index => $val) {
                        $paramName = 'language_id_' . $index;
                        $placeholders[] = ":$paramName";
                        $params[$paramName] = (int)$val;
                    }
                    $whereConditions[] = "language_id IN (" . implode(',', $placeholders) . ")";
                }
            }
        }
        
        $query = "SELECT COUNT(*) as total FROM film";
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
}
?>