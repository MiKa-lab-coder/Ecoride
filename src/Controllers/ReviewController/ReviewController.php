<?php

namespace App\Controllers\ReviewController;

use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use App\Models\Booking;
use App\Models\Rating;
use App\Services\Validator;
use App\Services\TokenValidator;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class ReviewController
 * Gère les opérations liées aux commentaires des utilisateurs, tels que la soumission et la récupération des commentaires.
 */

class ReviewController
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('error_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/error.log', 400));
    }

    // Soumission d'un commentaire pour un voyage
    public function submitReview(): void
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            // Récupération et validation du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $loggedInUserId = $decodedToken->data->id;

            // Récupération des données depuis le corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides. Le corps de la requête doit être un JSON valide.", 400);
            }

            // Validation de la présence des champs
            if (!isset($data['trip_id']) || !isset($data['rating'])) {
                throw new Exception("Les champs 'trip_id' et 'rating' sont obligatoires.", 400);
            }

            $tripId = (int)$data['trip_id'];
            $ratingValue = (int)$data['rating'];
            $ratedUserId = (int)$data['rated_user_id'];
            $commentContent = $data['comment'] ?? '';

            // Vérification de l'utilisateur
            $user = User::find($loggedInUserId);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID: " . $loggedInUserId);
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Vérification du voyage
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Voyage non trouvé.", 404);
            }

            // Vérification si l'utilisateur a déjà noté ce trajet pour cette personne
            if (Rating::hasUserRatedTrip($loggedInUserId, $tripId, $ratedUserId)) {
                throw new Exception("Vous avez déjà évalué cet utilisateur pour ce trajet.", 400);
            }

            // Enregistrement de la note (SQL)
            $rating = new Rating($ratedUserId, $loggedInUserId, $tripId, $ratingValue);
            if (!$rating->save()) {
                throw new Exception("Erreur lors de l'enregistrement de la note.", 500);
            }

            // Enregistrement du commentaire (NoSQL) si présent
            if (!empty($commentContent)) {
                $cleanContent = htmlspecialchars($commentContent, ENT_QUOTES, 'UTF-8');
                $review = new Review(
                    (string)$loggedInUserId,
                    (string)$tripId,
                    $cleanContent
                );
                $review->create();
            }

            http_response_code(201);
            echo json_encode(["message" => "Évaluation enregistrée avec succès."]);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la soumission du commentaire: " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    // Récupérer les avis reçus par l'utilisateur connecté
    public function getReceivedReviews(): void
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;

            // 1. Récupérer toutes les notes reçues (SQL)
            $ratings = Rating::getRatingsForUser($userId);
            
            $reviewsData = [];

            foreach ($ratings as $rating) {
                $tripId = (int)$rating['trip_id'];
                $authorId = (int)$rating['passenger_id']; // Celui qui a laissé la note

                // 2. Récupérer les infos du trajet
                $trip = Trip::findById($tripId);
                
                // 3. Récupérer les infos de l'auteur
                $author = User::find($authorId); // Correction: find() au lieu de findById()

                // 4. Récupérer le commentaire associé (NoSQL)
                $comment = Review::getReviewComment($tripId, $authorId);

                if ($trip && $author) {
                    $reviewsData[] = [
                        'trip_departure' => $trip->getDepartureLocation(),
                        'trip_arrival' => $trip->getArrivalLocation(),
                        'trip_date' => $trip->getDepartureDay()->format('d/m/Y'),
                        'author_firstname' => $author->getFirstname(),
                        'author_name' => $author->getName(),
                        'rating' => (int)$rating['rating_value'],
                        'comment' => $comment ?? 'Aucun commentaire.'
                    ];
                }
            }

            echo json_encode($reviewsData);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des avis reçus: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}
