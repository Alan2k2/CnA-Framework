<?php

declare(strict_types=1);

namespace CnA\Migrations;

class Blueprint
{
    /** @var array<array{name: string, type: string, length?: int, nullable?: bool, default?: mixed, primary?: bool, auto_increment?: bool, unique?: bool}> */
    protected array $columns = [];

    /** @var array<array{column: string, references: string, on: string, on_delete?: string}> */
    protected array $foreignKeys = [];

    public function __construct(protected string $table)
    {
    }

    public function id(string $column = 'id'): static
    {
        return $this->increments($column);
    }

    public function increments(string $column): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'increments',
            'primary' => true,
            'auto_increment' => true,
        ];
        return $this;
    }

    public function string(string $column, int $length = 255, bool $nullable = false): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'string',
            'length' => $length,
            'nullable' => $nullable,
        ];
        return $this;
    }

    public function text(string $column, bool $nullable = false): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'text',
            'nullable' => $nullable,
        ];
        return $this;
    }

    public function integer(string $column, bool $nullable = false, ?int $default = null): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'integer',
            'nullable' => $nullable,
            'default' => $default,
        ];
        return $this;
    }

    public function bigInteger(string $column, bool $nullable = false): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'bigint',
            'nullable' => $nullable,
        ];
        return $this;
    }

    public function decimal(string $column, int $precision = 10, int $scale = 2, bool $nullable = false): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => "decimal({$precision},{$scale})",
            'nullable' => $nullable,
        ];
        return $this;
    }

    public function boolean(string $column, bool $default = false): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'boolean',
            'default' => $default ? 1 : 0,
        ];
        return $this;
    }

    public function timestamp(string $column, bool $nullable = false): static
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'timestamp',
            'nullable' => $nullable,
        ];
        return $this;
    }

    public function timestamps(): static
    {
        $this->timestamp('created_at', true);
        $this->timestamp('updated_at', true);
        return $this;
    }

    public function foreignKey(string $column, string $referencesTable, string $referencesColumn = 'id', string $onDelete = 'CASCADE'): static
    {
        $this->foreignKeys[] = [
            'column' => $column,
            'references' => $referencesColumn,
            'on' => $referencesTable,
            'on_delete' => $onDelete,
        ];
        return $this;
    }

    public function toSql(string $driver = 'sqlite'): string
    {
        $columnDefs = [];

        foreach ($this->columns as $col) {
            $name = $col['name'];
            $type = $col['type'];

            $sqlType = match ($type) {
                'increments' => match ($driver) {
                    'sqlite' => 'INTEGER PRIMARY KEY AUTOINCREMENT',
                    'pgsql' => 'SERIAL PRIMARY KEY',
                    default => 'INT AUTO_INCREMENT PRIMARY KEY',
                },
                'string' => "VARCHAR(" . ($col['length'] ?? 255) . ")",
                'text' => 'TEXT',
                'integer' => 'INTEGER',
                'bigint' => 'BIGINT',
                'boolean' => match ($driver) {
                    'pgsql' => 'BOOLEAN',
                    default => 'TINYINT(1)',
                },
                'timestamp' => 'DATETIME',
                default => $type,
            };

            $def = "\"{$name}\" {$sqlType}";

            if (empty($col['primary']) && empty($col['nullable'])) {
                $def .= ' NOT NULL';
            }

            if (isset($col['default'])) {
                $def .= " DEFAULT '{$col['default']}'";
            }

            $columnDefs[] = $def;
        }

        foreach ($this->foreignKeys as $fk) {
            $columnDefs[] = "FOREIGN KEY (\"{$fk['column']}\") REFERENCES \"{$fk['on']}\" (\"{$fk['references']}\") ON DELETE {$fk['on_delete']}";
        }

        return "CREATE TABLE IF NOT EXISTS \"{$this->table}\" (\n  " . implode(",\n  ", $columnDefs) . "\n)";
    }
}

