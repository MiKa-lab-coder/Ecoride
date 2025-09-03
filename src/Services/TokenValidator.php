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
     */

    public function validateToken(string $token): object
    {
        //Verification du Jwt dans le header Authorization
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!$token) {
            http_response_code(401); // Non autorisé
            echo json_encode(['success' => false, 'message' => 'Token manquant.']);
            exit;
        }
        // Clean du préfixe "Bearer "
        if (str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        } else {
            http_response_code(400); // Requête incorrecte
            echo json_encode(['success' => false, 'message' => 'Format invalide.']);
            exit;
        }
        // Décodage et validation du token
        try {
            return $this->tokenManager->decode($token);
        } catch (Exception $e) {
            http_response_code(401); // Non autorisé
            echo json_encode(['success' => false, 'message' => 'Token invalide ou expiré.']);
            exit;
        }
    }
}