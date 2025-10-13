<?php

declare(strict_types=1);

namespace Trees\Database;

use PDO;
use PDOException;

class Connection
{
    private static $instance = null;
    private $pdo;
    
    private function __construct(array $config)
    {
        $driver = $config['driver'] ?? 'mysql';
        
        try {
            if ($driver === 'sqlite') {
                $dsn = "sqlite:{$config['database']}";
                $this->pdo = new PDO($dsn);
            } else {
                $dsn = $this->buildDsn($config);
                $this->pdo = new PDO(
                    $dsn,
                    $config['username'] ?? '',
                    $config['password'] ?? '',
                    $config['options'] ?? []
                );
            }
            
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function buildDsn(array $config): string
    {
        $driver = $config['driver'];
        $host = $config['host'];
        $port = $config['port'];
        $database = $config['database'];
        
        if ($driver === 'mysql') {
            $charset = $config['charset'] ?? 'utf8mb4';
            return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
        }
        
        if ($driver === 'pgsql') {
            return "pgsql:host={$host};port={$port};dbname={$database}";
        }
        
        throw new \InvalidArgumentException("Unsupported database driver: {$driver}");
    }
    
    public static function getInstance(array|null $config = null): self
    {
        if (self::$instance === null) {
            if ($config === null) {
                $config = require ROOT_PATH . '/config/database.php';
                $config = $config['connections'][$config['default']];
            }
            
            self::$instance = new self($config);
        }
        
        return self::$instance;
    }
    
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
    
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}