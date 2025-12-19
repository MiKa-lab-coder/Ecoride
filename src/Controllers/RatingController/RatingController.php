<?php

namespace App\Controllers\RatingController;

use App\Models\Rating;
use App\Models\Trip;
use App\Models\User;
use App\Services\Validator;
use App\services\TokenValidator;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class RatingController
 * Gère les opérations liées aux notes des conducteurs.
 */
class RatingController
{
    // Soumission d'une note pour un conducteur
    public function submitRating(int $ratedUserId, int $raterUserId, int $tripId, int $ratingValue): void
    {
        header('Application-Type: application/json');

        try {

            // Récupération et validation du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }
            if ($user->getUserId() !== $raterUserId) {
                throw new Exception("Vous ne pouvez pas noter pour un autre utilisateur.", 403);
            }
            // Récupération du trajet
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            if ($trip->getDriverId() !== $ratedUserId) {
                throw new Exception("L'utilisateur noté n'est pas le conducteur de ce trajet.", 400);
            }
            // Validation de la note
            $validator = new Validator();
            if (!$validator->validateDriverRating($ratingValue)) {
                throw new Exception("Note invalide. La note doit être un entier entre 1 et 5.", 400);
            }
            // Enregistrement de la note
            $rating = new Rating($ratedUserId, $raterUserId, $tripId, $ratingValue, 0);
            if ($rating->save()) {
                http_response_code(201);
                echo json_encode(["message" => "Note enregistrée avec succès."]);
            } else {
                throw new Exception("Erreur lors de l'enregistrement de la note.", 500);
            }
        } catch (Exception $e) {
            $logger = new Logger('rating_controller');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/rating_controller.log', 400));
            $logger->error("Erreur d'enregistrement de la note " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        exit;
    }
    // Affichage de la note d'un utilisateur (pour son profil)
    public function getUserRating(int $userId): void
    {
        header('Application-Type: application/json');

        try {
            // Récupération et validation du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $requestingUserId = $decodedToken->sub;
            // Récupération de l'utilisateur
            $requestingUser = User::find($requestingUserId);
            if (!$requestingUser) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Récupération de la note moyenne
            $averageRating = Rating::getAverageRatingForUser($userId);
            http_response_code(200);
            echo json_encode(["user_id" => $userId, "average_rating" => $averageRating]);
        } catch (Exception $e) {
            $logger = new Logger('rating_controller');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/rating_controller.log', 400));
            $logger->error("Erreur de récupération de la note " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
        exit;
    }
}