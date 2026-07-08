<?php

namespace Config;

use PDO;
use PDOException;

class Database {
    private static $host = 'localhost';
    private static $db_name = 'smart_hall_db';
    private static $username = 'root';
    private static $password = '';
    private static $conn = null;

    public static function getConnection() {
        if (self::$conn !== null) {
            return self::$conn;
        }

        try {
            self::$conn = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4",
                self::$username,
                self::$password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $exception) {
            // In a production app, do not expose exception details directly to the client.
            error_log("Database Connection Error: " . $exception->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Database connection failed."
            ]);
            exit;
        }

        return self::$conn;
    }
}
