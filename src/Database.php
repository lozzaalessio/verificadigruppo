<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

/**
 * Singleton PDO connection factory.
 * Legge le credenziali da variabili d'ambiente (file .env opzionale).
 */
class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host   = $_ENV['DB_HOST'] ?? 'localhost';
            $port   = $_ENV['DB_PORT'] ?? '3306';
            $dbname = $_ENV['DB_NAME'] ?? 'FornitoriPezziDB';
            $user   = $_ENV['DB_USER'] ?? 'root';
            $pass   = $_ENV['DB_PASS'] ?? '';

            try {
                self::$instance = new PDO(
                    "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \RuntimeException('Connessione DB fallita: ' . $e->getMessage(), 500);
            }
        }

        return self::$instance;
    }

    /** Permette di iniettare una connessione esterna (utile nei test). */
    public static function setConnection(PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }
}
