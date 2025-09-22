<?php
namespace App\Models\Database;
use App\Config\Config;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    private function __clone() {}

    public static function getInstance(): PDO
    {
        // On s'assure que l'instance est créée une seule fois
        if (self::$instance === null) {
            try {
                // On définit toutes les variables de connexion
                $host = Config::get("DB_HOST");
                $port = Config::get("DB_PORT", "3306");
                $dbname = Config::get("DB_NAME");
                $user = Config::get("DB_USER");
                $password = Config::get("DB_PASSWORD");

                // On construit le dsn à partir des variables de .env
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];

                // On crée l'instance de PDO et on la stocke
                self::$instance = new PDO($dsn, $user, $password, $options);

            } catch (PDOException $e) {
                // On gère l'erreur de connexion
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}