<?php
class Migration {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function checkAndRunMigrations() {
        $tables = ['film', 'customer', 'rental', 'store'];
        $missingTables = [];
        
        foreach ($tables as $table) {
            if (!$this->tableExists($table)) {
                $missingTables[] = $table;
            }
        }
        
        if (empty($missingTables)) {
            return ['success' => true, 'message' => 'All tables exist'];
        }
        
        foreach ($missingTables as $table) {
            $this->runMigration($table);
        }
        
        return [
            'success' => true, 
            'message' => 'Migrations completed',
            'created_tables' => $missingTables
        ];
    }
    
    private function tableExists($tableName) {
        try {
            $query = "SHOW TABLES LIKE :table_name";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':table_name', $tableName);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    private function runMigration($tableName) {
        $sqlFile = __DIR__ . "/../../resources/sql/{$tableName}.sql";
        
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file for table '{$tableName}' not found");
        }
        
        $sql = file_get_contents($sqlFile);
        
        try {
            $queries = $this->splitSQL($sql);
            
            foreach ($queries as $query) {
                if (!empty(trim($query))) {
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                }
            }
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Migration failed for table '{$tableName}': " . $e->getMessage());
        }
    }
    
    private function splitSQL($sql) {
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        $queries = explode(';', $sql);
        
        return array_filter(array_map('trim', $queries));
    }
}
?>