<?php

namespace App\Services;

use Exception;

class TokenValidator
{

    private TokenManager $tokenManager;

    public function __construct()
    {
        $this->tokenManager = new TokenManager();
    }

    /**
     * Réceptionne, clean et valide un token JWT.
     * @throws Exception
     */

    public function validateToken(string $token): object
    {
        //Verification du Jwt dans le header Authorization
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$token) {
            throw new Exception("Token manquant.", 401);// Non autorisé
        }
        // Clean du préfixe "Bearer "
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        } else {
            throw new Exception("Format de token invalide.", 400); // Mauvaise requête
        }
        // Décodage et validation du token
        try {
            return $this->tokenManager->decode($token);
        } catch (Exception $e) {
            throw new Exception("Token invalide.", 401);
        }
    }
}