<?php
require_once 'models/Film.php';

class FilmController {
    private $model;
    
    public function __construct($db) {
        $this->model = new Film($db);
    }
    
    public function getAll() {
        try {
            $filters = [];
            if (isset($_GET['title'])) $filters['title'] = $_GET['title'];
            if (isset($_GET['release_year'])) $filters['release_year'] = $_GET['release_year'];
            if (isset($_GET['language_id'])) $filters['language_id'] = $_GET['language_id'];
            if (isset($_GET['original_language_id'])) $filters['original_language_id'] = $_GET['original_language_id'];
            if (isset($_GET['length'])) $filters['length'] = $_GET['length'];
            if (isset($_GET['rating'])) $filters['rating'] = $_GET['rating'];
            
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