<?php

namespace App\Services;

// on utilise la bibliothèque Firebase JWT pour gérer les tokens JWT
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class TokenManager
{
    // Clé secrète
    private string $secretKey;

    // Algorithme de chiffrement
    private string $algorithm = 'HS256';

    public function __construct()
    {
        // Récupère la clé secrète dans .env
        $this->secretKey = getenv('JWT_SECRET');
        if (!$this->secretKey) {
            throw new Exception("La clé JWT_SECRET n'est pas définie dans le fichier .env.");
        }
    }

    /**
     * Génère un nouveau jeton JWT.
     * @param array $sequence Les données à inclure dans le jeton.
     * @return string Le jeton JWT généré.
     */
    public function generateToken(array $sequence): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + 3600; // Valide pour 1 heure

        $data = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'data' => $sequence
        ];

        return JWT::encode($data, $this->secretKey, $this->algorithm);
    }

    /**
     * Décode et valide un jeton JWT.
     * @param string $token Le jeton JWT à décoder.
     */
    public function decode(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded;
        } catch (Exception $e) {
            // Journalisation de l'erreur
            $logger = new Logger('token_errors');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/token_errors.log', Logger::ERROR));
            $logger->error('Erreur de décodage du token JWT: ' . $e->getMessage());

            throw new Exception('Token invalide ou expiré.');
        }
    }
}