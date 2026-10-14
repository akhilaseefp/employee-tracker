<?php
// Database connection and helper functions
// Uses PDO with prepared statements for all queries

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;

    /**
     * Get or create the PDO database connection.
     * Returns a single shared instance for the request.
     */
    public static function getConnection(): PDO {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            try {
                // Pull $pdoOptions from config.php global scope
                global $pdoOptions;
                $options = $pdoOptions ?? [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
                // Ensure correct charset
                self::$pdo->exec("SET NAMES utf8mb4");
            } catch (PDOException $e) {
                // Log error securely; do not expose details to user
                error_log('Database connection failed: ' . $e->getMessage());
                throw new RuntimeException('Database connection unavailable. Please contact support.');
            }
        }
        return self::$pdo;
    }

    /**
     * Execute a query with prepared parameters.
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for placeholders
     * @return PDOStatement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row as an associative array.
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Fetch all rows as an array of associative arrays.
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert a record and return the new ID.
     */
    public static function insert(string $table, array $data): int {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ':' . $col, $columns);

        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }

        self::query($sql, $params);
        return (int) self::getConnection()->lastInsertId();
    }

    /**
     * Update records matching a WHERE clause.
     */

    public static function update(string $table, array $data, string $whereColumn, $whereValue): int {
        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
            $params[':' . $key] = $value;
        }

        $params[':where'] = $whereValue;

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$whereColumn} = :where";

        // Execute the update and keep the resulting statement.
        $stmt = self::query($sql, $params);

        // rowCount() belongs to PDOStatement, not the PDO connection.
        return $stmt->rowCount();
    }


    /**
     * Begin a database transaction.
     */
    public static function beginTransaction(): void {
        self::getConnection()->beginTransaction();
    }

    /**
     * Commit a database transaction.
     */
    public static function commit(): void {
        self::getConnection()->commit();
    }

    /**
     * Rollback a database transaction.
     */
    public static function rollBack(): void {
        self::getConnection()->rollBack();
    }
}
?>