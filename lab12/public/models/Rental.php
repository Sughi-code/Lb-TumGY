<?php
class Rental {
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
                if ($field === 'inventory_id' && is_numeric($value)) {
                    $whereConditions[] = "inventory_id = :inventory_id";
                    $params['inventory_id'] = (int)$value;
                } elseif ($field === 'customer_id' && is_numeric($value)) {
                    $whereConditions[] = "customer_id = :customer_id";
                    $params['customer_id'] = (int)$value;
                } elseif ($field === 'staff_id' && is_numeric($value)) {
                    $whereConditions[] = "staff_id = :staff_id";
                    $params['staff_id'] = (int)$value;
                } elseif ($field === 'return_date') {
                    if (str_starts_with($value, '>')) {
                        $dateValue = substr($value, 1);
                        if ($this->isValidDate($dateValue)) {
                            $whereConditions[] = "return_date > :return_date_gt";
                            $params['return_date_gt'] = $dateValue;
                        }
                    } elseif (str_starts_with($value, '<')) {
                        $dateValue = substr($value, 1);
                        if ($this->isValidDate($dateValue)) {
                            $whereConditions[] = "return_date < :return_date_lt";
                            $params['return_date_lt'] = $dateValue;
                        }
                    } else {
                        $rangeParts = explode('<', $value);
                        if (count($rangeParts) === 2 && $this->isValidDate($rangeParts[0]) && $this->isValidDate($rangeParts[1])) {
                            $whereConditions[] = "return_date BETWEEN :return_date_min AND :return_date_max";
                            $params['return_date_min'] = $rangeParts[0];
                            $params['return_date_max'] = $rangeParts[1];
                        } elseif ($this->isValidDate($value)) {
                            $whereConditions[] = "return_date = :return_date";
                            $params['return_date'] = $value;
                        }
                    }
                }
            }
        }
        
        $query = "SELECT * FROM rental";
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $allowedSort = ['rental_date', 'return_date', 'rental_id'];
        if ($sort && in_array($sort, $allowedSort)) {
            $query .= " ORDER BY $sort $order";
        } else {
            $query .= " ORDER BY rental_id ASC";
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
        $query = "SELECT * FROM rental WHERE rental_id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("Rental not found", 404);
            }
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function create($data) {
        $required = ['inventory_id', 'customer_id', 'staff_id'];
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || !is_numeric($data[$field]) || $data[$field] <= 0) {
                $errors[] = "Valid $field is required";
            }
        }
        
        if (isset($data['return_date']) && !empty($data['return_date']) && 
            !$this->isValidDate($data['return_date'])) {
            $errors[] = "Invalid return date format";
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors), 400);
        }
        
        $fields = ['rental_date', 'inventory_id', 'customer_id', 
                  'return_date', 'staff_id', 'last_update'];
        
        $values = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $values[] = ":$field";
                $params[$field] = $data[$field];
            } else {
                if ($field === 'rental_date') {
                    $values[] = ":rental_date";
                    $params['rental_date'] = date('Y-m-d H:i:s');
                } elseif ($field === 'last_update') {
                    $values[] = ":last_update";
                    $params['last_update'] = date('Y-m-d H:i:s');
                } else {
                    $values[] = "NULL";
                }
            }
        }
        
        $query = "INSERT INTO rental (" . implode(', ', $fields) . ") 
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
        $rental = $this->getById($id);
        
        $updates = [];
        $params = ['id' => (int)$id];
        
        $allowed = ['return_date', 'staff_id'];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                if ($field === 'return_date' && !empty($data[$field]) && 
                    !$this->isValidDate($data[$field])) {
                    throw new Exception("Invalid return date format", 400);
                }
                
                if ($field === 'staff_id' && (!is_numeric($data[$field]) || $data[$field] <= 0)) {
                    throw new Exception("Invalid staff_id", 400);
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
        
        $query = "UPDATE rental SET " . implode(', ', $updates) . " WHERE rental_id = :id";
        
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
        $rental = $this->getById($id);
        
        $query = "DELETE FROM rental WHERE rental_id = :id";
        
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
                if ($field === 'customer_id' && is_numeric($value)) {
                    $whereConditions[] = "customer_id = :customer_id";
                    $params['customer_id'] = (int)$value;
                }
            }
        }
        
        $query = "SELECT COUNT(*) as total FROM rental";
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
    
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $d && $d->format('Y-m-d H:i:s') === $date;
    }
}
?>