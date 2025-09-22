<?php
namespace App\Config;
use Dotenv\Dotenv;

class Config
{

    /*
     * @param string $path => chemin vers le dossier .env
     */
    public static function load($path = __DIR__ . "../"): void
    {
        // On remonte de deux niveaux pour trouver la racine du projet
            $path = dirname(__DIR__, 2);

        // On vérifie si le fichier .env existe avant de tenter de le charger
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();

    }

    public static function get(string $key, $default=null) {
        return $_ENV[$key] ?? $default;
    }
}