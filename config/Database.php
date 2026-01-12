<?php
/**
 * Database connection class
 */
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            try {
                if (function_exists('ensureUsersUsernameColumn')) {
                    ensureUsersUsernameColumn($this->conn);
                } else {
                    $columns = $this->conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
                    $fields = array_column($columns, 'Field');
                    if (!in_array('username', $fields, true) && in_array('name', $fields, true)) {
                        $this->conn->exec("ALTER TABLE users CHANGE COLUMN name username VARCHAR(255) NOT NULL");
                    }
                }
            } catch (Exception $e) {
                // ignore
            }
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
        
        return $this->conn;
    }
}
?>
