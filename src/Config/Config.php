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
        //on verifie si le fichier .env exist avant de tenter de le charger
        if (file_exists($path . '.env')) {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->load();
        }
    }

    public static function get(string $key, $default=null) {
        return $_ENV[$key] ?? $default;
    }
}