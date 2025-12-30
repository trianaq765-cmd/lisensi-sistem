<?php
require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", DB_HOST, DB_PORT, DB_NAME);
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $this->initTables();
    }
    
    private function initTables() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS licenses (
                id SERIAL PRIMARY KEY,
                license_key VARCHAR(50) UNIQUE NOT NULL,
                product_name VARCHAR(100) DEFAULT 'MyTool',
                license_type VARCHAR(20) DEFAULT 'BASIC',
                status VARCHAR(20) DEFAULT 'active',
                max_activations INT DEFAULT 1,
                current_activations INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NULL,
                customer_email VARCHAR(255) NULL,
                notes TEXT NULL
            );
            CREATE TABLE IF NOT EXISTS activations (
                id SERIAL PRIMARY KEY,
                license_id INT NOT NULL,
                hardware_id VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }
    
    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function lastId() {
        return $this->pdo->lastInsertId();
    }
}
