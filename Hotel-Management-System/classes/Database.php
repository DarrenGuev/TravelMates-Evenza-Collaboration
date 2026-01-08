<?php

class Database
{
    private static ?Database $instance = null;
    private mysqli $connection;
    private string $host;
    private string $username;
    private string $password;
    private string $database;

    private function __construct()
    {
        $this->host = 'localhost';
        $this->username = 'root';
        $this->password = '';
        $this->database = 'travelMates';

        $this->connect();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    //Get the singleton instance of Database @return Database
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect(): void
    {
        $this->connection = new mysqli(
            $this->host,
            $this->username,
            $this->password,
            $this->database
        );

        if ($this->connection->connect_error) {
            die("Connection Failed: " . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }

    public function query(string $query)
    {
        return $this->connection->query($query);
    }

    public function prepare(string $query)
    {
        return $this->connection->prepare($query);
    }

    public function escape(string $string): string
    {
        return $this->connection->real_escape_string($string);
    }

    public function lastInsertId()
    {
        return $this->connection->insert_id;
    }

    public function affectedRows(): int
    {
        return $this->connection->affected_rows;
    }

    public function error(): string
    {
        return $this->connection->error;
    }

    public function beginTransaction(): bool
    {
        return $this->connection->begin_transaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        return $this->connection->rollback();
    }

    public function executeStatement(string $query, string $types = '', array $params = [])
    {
        $stmt = $this->prepare($query);
        
        if ($stmt === false) {
            return false;
        }

        if (!empty($types) && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $result = $stmt->execute();
        
        if ($result === false) {
            return false;
        }

        $resultSet = $stmt->get_result();
        
        //for SELECT queries, return the result set
        if ($resultSet !== false) {
            return $resultSet;
        }
        
        //for INSERT/UPDATE/DELETE, return true
        return true;
    }

    public function fetchAll($result): array
    {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchOne($result): ?array
    {
        return $result->fetch_assoc();
    }

    public function close(): void
    {
        $this->connection->close();
    }
}
