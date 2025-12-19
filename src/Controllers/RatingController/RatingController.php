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

class RatingController
{
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('rating_controller');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/rating.log', Logger::INFO));
    }

    /**
     * Soumet une note et un commentaire pour un trajet terminé.
     */
    public function submitRating(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $raterUserId = $decodedToken->data->id;

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }

            $tripId = $data['trip_id'] ?? null;
            $ratingValue = $data['rating'] ?? null;
            $comment = $data['comment'] ?? '';

            if (!$tripId || !$ratingValue) {
                throw new Exception("L'ID du trajet et la note sont obligatoires.", 400);
            }

            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }

            // Déterminer qui est noté (le conducteur ou le passager)
            $ratedUserId = ($raterUserId === $trip->getDriverId()) 
                ? ($data['rated_user_id'] ?? null) // Le conducteur note un passager
                : $trip->getDriverId(); // Le passager note le conducteur

            if (!$ratedUserId) {
                throw new Exception("L'utilisateur à noter n'a pas été spécifié.", 400);
            }

            $validator = new Validator();
            if (!$validator->validateDriverRating($ratingValue)) {
                throw new Exception("Note invalide. La note doit être un entier entre 1 et 5.", 400);
            }

            // Vérifier si une note a déjà été laissée
            if (Rating::hasUserRatedTrip($raterUserId, $tripId, $ratedUserId)) {
                throw new Exception("Vous avez déjà noté cet utilisateur pour ce trajet.", 409); // 409 Conflict
            }

            $rating = new Rating($ratedUserId, $raterUserId, $tripId, $ratingValue, $comment);
            if ($rating->save()) {
                http_response_code(201);
                echo json_encode(["message" => "Évaluation enregistrée avec succès."]);
            } else {
                throw new Exception("Erreur lors de l'enregistrement de l'évaluation.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la soumission de l'évaluation: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function getUserRating(int $userId): void
    {
        header('Content-Type: application/json');
        try {
            $averageRating = Rating::getAverageRatingForUser($userId);
            http_response_code(200);
            echo json_encode(["user_id" => $userId, "average_rating" => $averageRating]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["error" => "Erreur lors de la récupération de la note."]);
        }
    }
}
