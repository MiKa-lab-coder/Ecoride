<?php

/**
 * Point d'entrée unique de l'application.
 * Initialise l'environnement et délègue le routage.
 */

// On appelle le fichier autoload de composer
require_once __DIR__ . '/../vendor/autoload.php';

// On charge les variables d'environnement
\App\Config\Config::load(dirname(__DIR__));

// Définir les entêtes autorisés pour les requêtes (CORS)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-control-allow-headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Gérer les requêtes OPTIONS (Pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Déléguer le routage au fichier dédié dans src/
require_once __DIR__ . '/../src/routes.php';
