<?php

namespace App\Models\Database;

use App\Config\Config;
use PDO;
use PDOException;

class Database
{
    //propriété static pour stocker l'instance unique de PDO
    private static ?PDO $instance = null;

    //le construct est privé pour empecher la creation d'objet via new
    private function __construct()
    {
    }

    //la méthode clone est privé pour empecher de cloner l'instance
    private function __clone()
    {
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {

            //on construit le dsn (data source name) avec les infos du fichier .env
            $dsn = sprintf("mysql:host=%s;port=%s;charset=utf8mb4", Config::get("DB_HOST"), Config::get("DB_PORT", "3306"), Config::get("DB_NAME"));
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,//lance les exceptions en cas d'erreur sql
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,//recup les resultats sous forme de tableau
            ];
        }
        //on creer l'instance de pdo et on la stock
        try {
            self::$instance = new PDO($dsn, Config::get("DB_USER"), Config::get("DB_PASSWORD"), $options);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
        return self::$instance;
    }
}