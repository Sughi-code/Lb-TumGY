<?php
require_once 'models/Customer.php';

class CustomerController {
    private $model;
    
    public function __construct($db) {
        $this->model = new Customer($db);
    }
    
    public function getAll() {
        try {
            $filters = [];
            if (isset($_GET['store_id'])) $filters['store_id'] = $_GET['store_id'];
            if (isset($_GET['active'])) $filters['active'] = $_GET['active'];
            
            $sort = $_GET['sort'] ?? null;
            $order = $_GET['order'] ?? 'ASC';
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 50;
            
            $data = $this->model->getAll($filters, $sort, $order, $page, $limit);
            $total = $this->model->getTotalCount($filters);
            
            $response = [
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
            return $response;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function getById($id) {
        try {
            $data = $this->model->getById($id);
            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function create($data) {
        try {
            $result = $this->model->create($data);
            return ['success' => true, 'data' => $result, 'status' => 201];
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function update($id, $data) {
        try {
            $result = $this->model->update($id, $data);
            return ['success' => true, 'data' => $result];
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    public function delete($id) {
        try {
            $this->model->delete($id);
            return ['success' => true, 'status' => 204];
        } catch (Exception $e) {
            throw $e;
        }
    }
}
?>