<?php

namespace App\Models\Database;

use App\Config\Config;
use MongoDB\Client;
use MongoDB\Driver\Exception\Exception as MongoDBException;

// Singleton pour la connexion à la base de données MongoDB
class MongoDatabase
{
    private static ?Client $instance = null;

    private function __construct()
    {
    }

    //la méthode clone est privé pour empecher de cloner l'instance
    private function __clone()
    {
    }

    public static function getInstance(): Client
    {
        if (self::$instance === null) {
            $uri = sprintf("mongodb://%s:%s",
                Config::get("DB_HOST"),
                Config::get("DB_PORT", "27017")
            );
            // Si variable identification définies
            if (Config::get("DB_USER") && Config::get("DB_PASSWORD")) {
                $uri = sprintf(
                    "mongodb://%s:%s@%s:%s",
                    Config::get("DB_USER"),
                    Config::get("DB_PASSWORD"),
                    Config::get("DB_HOST"),
                    Config::get("DB_PORT", "27017")
                );
            }
            try {
                // Créer l'instance de Client et la stocker
                self::$instance = new Client($uri);

                // Test de la connexion avec une commande ping
                self::$instance->selectDatabase('admin')->command(['ping' => 1]);
            } catch (MongoDBException $e) {
                die("Échec de la connexion à MongoDB: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}