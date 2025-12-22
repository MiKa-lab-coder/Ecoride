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
            // Utiliser des variables d'environnement spécifiques à MongoDB
            $host = Config::get("MONGO_HOST", "mongo");
            $port = Config::get("MONGO_PORT", "27017");
            $user = Config::get("MONGO_INITDB_ROOT_USERNAME");
            $pass = Config::get("MONGO_INITDB_ROOT_PASSWORD");

            $uri = sprintf("mongodb://%s:%s", $host, $port);
            
            // Si les variables d'identification sont définies
            if ($user && $pass) {
                $uri = sprintf(
                    "mongodb://%s:%s@%s:%s",
                    $user,
                    $pass,
                    $host,
                    $port
                );
            }
            try {
                // Créer l'instance de Client et la stocker
                self::$instance = new Client($uri);

                // Test de la connexion avec une commande ping
                self::$instance->selectDatabase('admin')->command(['ping' => 1]);
            } catch (MongoDBException $e) {
                // Renvoyer une exception au lieu de 'die' pour ne pas casser le JSON
                throw new \RuntimeException("Échec de la connexion à MongoDB: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
