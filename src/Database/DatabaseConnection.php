<?php

namespace Framework\Database;

use PDO;
use PDOException;
use Framework\Exceptions\FrameworkException;

/**
 * Database Connection
 * 
 * Handles database connection and provides methods for querying
 */
class DatabaseConnection
{
    /**
     * PDO instance
     * 
     * @var PDO|null
     */
    protected ?PDO $pdo = null;
    
    /**
     * Connection configuration
     * 
     * @var array
     */
    protected array $config;
    
    /**
     * Create a new database connection
     * 
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Get the PDO connection
     * 
     * @return PDO
     * @throws FrameworkException
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            try {
                $dsn = $this->buildDsn();
                $options = $this->config['options'] ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['username'] ?? null,
                    $this->config['password'] ?? null,
                    $options
                );
            } catch (PDOException $e) {
                throw new FrameworkException("Database connection failed: {$e->getMessage()}", 500, $e);
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Build the DSN string based on the driver
     * 
     * @return string
     * @throws FrameworkException
     */
    protected function buildDsn(): string
    {
        $driver = $this->config['driver'] ?? 'mysql';
        
        switch ($driver) {
            case 'mysql':
                return $this->buildMysqlDsn();
            case 'sqlite':
                return $this->buildSqliteDsn();
            default:
                throw new FrameworkException("Unsupported database driver: {$driver}");
        }
    }
    
    /**
     * Build a MySQL DSN
     * 
     * @return string
     */
    protected function buildMysqlDsn(): string
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        
        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }
    
    /**
     * Build a SQLite DSN
     * 
     * @return string
     */
    protected function buildSqliteDsn(): string
    {
        $path = $this->config['path'] ?? ':memory:';
        return "sqlite:{$path}";
    }
    
    /**
     * Execute a select query and return all results
     * 
     * @param string $query
     * @param array $params
     * @return array
     * @throws FrameworkException
     */
    public function select(string $query, array $params = []): array
    {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new FrameworkException("Database query failed: {$e->getMessage()}", 500, $e);
        }
    }
    
    /**
     * Execute a select query and return a single row
     * 
     * @param string $query
     * @param array $params
     * @return array|null
     * @throws FrameworkException
     */
    public function selectOne(string $query, array $params = []): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            throw new FrameworkException("Database query failed: {$e->getMessage()}", 500, $e);
        }
    }
    
    /**
     * Execute an insert, update, or delete query
     * 
     * @param string $query
     * @param array $params
     * @return int
     * @throws FrameworkException
     */
    public function execute(string $query, array $params = []): int
    {
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new FrameworkException("Database query failed: {$e->getMessage()}", 500, $e);
        }
    }
    
    /**
     * Insert a row and return the ID
     * 
     * @param string $table
     * @param array $data
     * @return int|string
     * @throws FrameworkException
     */
    public function insert(string $table, array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $values = array_values($data);
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute($values);
            
            return $this->getConnection()->lastInsertId();
        } catch (PDOException $e) {
            throw new FrameworkException("Database insert failed: {$e->getMessage()}", 500, $e);
        }
    }
    
    /**
     * Update rows in a table
     * 
     * @param string $table
     * @param array $data
     * @param string $where
     * @param array $whereParams
     * @return int
     * @throws FrameworkException
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $setClauses[] = "{$column} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setClauses);
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($values, $whereParams);
        
        return $this->execute($query, $params);
    }
    
    /**
     * Delete rows from a table
     * 
     * @param string $table
     * @param string $where
     * @param array $params
     * @return int
     * @throws FrameworkException
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $query = "DELETE FROM {$table} WHERE {$where}";
        
        return $this->execute($query, $params);
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }
}