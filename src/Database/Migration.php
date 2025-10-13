<?php

declare(strict_types=1);

namespace Trees\Database;

abstract class Migration
{
    protected $connection;
    
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    abstract public function up(): void;
    abstract public function down(): void;
    
    protected function createTable(string $table, callable $callback): void
    {
        $schema = new Schema($this->connection, $table);
        $callback($schema);
        $schema->create();
    }
    
    protected function dropTable(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS {$table}";
        $this->connection->execute($sql);
    }
    
    protected function addColumn(string $table, string $column, string $type): void
    {
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$type}";
        $this->connection->execute($sql);
    }
    
    protected function dropColumn(string $table, string $column): void
    {
        $sql = "ALTER TABLE {$table} DROP COLUMN {$column}";
        $this->connection->execute($sql);
    }
}