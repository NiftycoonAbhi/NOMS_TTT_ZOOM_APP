<?php
/**
 * Database Connection Configuration
 * TTT ZOOM Attendance Management System
 * 
 * This file handles the database connection using improved error handling
 * and configuration management.
 */

// Include application configuration
require_once __DIR__ . '/../config.php';

class DatabaseConnection {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Create connection using configuration constants
            $this->connection = new mysqli(
                DB_HOST,
                DB_USERNAME,
                DB_PASSWORD,
                DB_NAME
            );
            
            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset
            $this->connection->set_charset(DB_CHARSET);
            
            // Set timezone to Indian Standard Time
            $this->connection->query("SET time_zone = '+05:30'");
            
            // Set SQL mode for better compatibility
            $this->connection->query("SET sql_mode = 'TRADITIONAL'");
            
            // Log successful connection in debug mode
            if (isDebugMode()) {
                logEvent('Database connection established successfully', 'DEBUG');
            }
            
        } catch (Exception $e) {
            logEvent('Database connection failed: ' . $e->getMessage(), 'ERROR');
            
            if (isDebugMode()) {
                die("Database Error: " . $e->getMessage());
            } else {
                die("Database connection error. Please contact administrator.");
            }
        }
    }
    
    /**
     * Get singleton instance of database connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the MySQLi connection object
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a prepared statement safely
     */
    public function executePrepared($query, $types = '', $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            logEvent('Database query failed: ' . $e->getMessage(), 'ERROR', [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }
    
    /**
     * Get last insert ID
     */
    public function getLastInsertId() {
        return $this->connection->insert_id;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->autocommit(false);
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $result = $this->connection->commit();
        $this->connection->autocommit(true);
        return $result;
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $result = $this->connection->rollback();
        $this->connection->autocommit(true);
        return $result;
    }
    
    /**
     * Close connection (called automatically on script end)
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Create global connection instance for backward compatibility
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();

// Function to get database connection (for use in other files)
function getDbConnection() {
    return DatabaseConnection::getInstance()->getConnection();
}

// Function to execute safe queries
function executeQuery($query, $types = '', $params = []) {
    return DatabaseConnection::getInstance()->executePrepared($query, $types, $params);
}
?>