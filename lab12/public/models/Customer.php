<?php
class Customer {
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
                if (in_array($field, ['store_id', 'active'])) {
                    $whereConditions[] = "$field = :$field";
                    $params[$field] = $value;
                }
            }
        }
        
        $query = "SELECT * FROM customer";
        if (!empty($whereConditions)) {
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }
        
        $allowedSort = ['first_name', 'last_name', 'customer_id'];
        if ($sort && in_array($sort, $allowedSort)) {
            $query .= " ORDER BY $sort $order";
        } else {
            $query .= " ORDER BY customer_id ASC";
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
        $query = "SELECT * FROM customer WHERE customer_id = :id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception("Customer not found", 404);
            }
            
            return $result;
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
    
    public function create($data) {
        $required = ['store_id', 'first_name', 'last_name', 'email', 'address_id'];
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Field '$field' is required";
            }
        }
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        if (isset($data['store_id']) && (!is_numeric($data['store_id']) || $data['store_id'] <= 0)) {
            $errors[] = "Invalid store_id";
        }
        
        if (isset($data['address_id']) && (!is_numeric($data['address_id']) || $data['address_id'] <= 0)) {
            $errors[] = "Invalid address_id";
        }
        
        if (isset($data['active']) && !is_numeric($data['active'])) {
            $errors[] = "Invalid active value";
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(", ", $errors), 400);
        }
        
        $fields = ['store_id', 'first_name', 'last_name', 'email', 'address_id', 
                  'active', 'create_date', 'last_update'];
        $values = [];
        $params = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[] = ":$field";
                $params[$field] = $data[$field];
            } else {
                if ($field === 'active') {
                    $values[] = ":active";
                    $params['active'] = 1;
                } elseif ($field === 'create_date' || $field === 'last_update') {
                    $values[] = ":$field";
                    $params[$field] = date('Y-m-d H:i:s');
                } else {
                    $values[] = "NULL";
                }
            }
        }
        
        $query = "INSERT INTO customer (" . implode(', ', $fields) . ") 
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
        $customer = $this->getById($id);
        
        $updates = [];
        $params = ['id' => (int)$id];
        
        $allowed = ['store_id', 'first_name', 'last_name', 'email', 'address_id', 'active'];
        
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                if ($field === 'email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format", 400);
                }
                
                if (in_array($field, ['store_id', 'address_id', 'active']) && 
                    !is_numeric($data[$field])) {
                    throw new Exception("Invalid value for $field", 400);
                }
                
                if ($field === 'first_name' || $field === 'last_name') {
                    if (strlen($data[$field]) > 45) {
                        throw new Exception("$field is too long (max 45 characters)", 400);
                    }
                }
                
                if ($field === 'email' && strlen($data[$field]) > 50) {
                    throw new Exception("Email is too long (max 50 characters)", 400);
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
        
        $query = "UPDATE customer SET " . implode(', ', $updates) . " WHERE customer_id = :id";
        
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
        $customer = $this->getById($id);
        
        $query = "DELETE FROM customer WHERE customer_id = :id";
        
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
                if (in_array($field, ['store_id', 'active'])) {
                    $whereConditions[] = "$field = :$field";
                    $params[$field] = $value;
                }
            }
        }
        
        $query = "SELECT COUNT(*) as total FROM customer";
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