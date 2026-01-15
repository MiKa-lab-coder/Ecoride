<?php

namespace App\Controllers\ReviewController;

use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use App\Models\Booking;
use App\Models\Rating;
use App\Services\Validator;
use App\Services\Mailler;
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

    /**
     * Soumettre un commentaire
     */
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
            // Vérification de la validité de la note
            $validator = new Validator();
            if (!$validator->validateDriverRating($ratingValue)) {
                throw new Exception("La note doit être comprise entre 1 et 5.", 400);
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

    /**
     * Récupération des avis reçus par un utilisateur
     */
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
    
    /**
     * Recuperer les reviews qui on un status pending
     */
    public function getPendingReviews(): void
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            
            // recuperation des autorisations
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $roleId = $decodedToken->data->role;

            // On vérifie que l'utilisateur est bien un admin (1) ou un modérateur (2)
            if ($roleId != 1 && $roleId != 2) {
                throw new Exception("Accès non autorisé.", 403);
            }

            $reviews = Review::getPendingReviews();
            $reviewsData = [];

            foreach ($reviews as $review) {
                $trip = Trip::findById((int)$review->getTripId());
                $user = User::find((int)$review->getUserId());

                // Récupérer la note associée depuis la table SQL
                $rating = Rating::getRatingForTripByUser((int)$review->getTripId(), (int)$review->getUserId());

                if ($trip && $user && $rating) {
                    $reviewsData[] = [
                        'review_id' => $review->getReviewId(),
                        'trip_departure' => $trip->getDepartureLocation(),
                        'trip_arrival' => $trip->getArrivalLocation(),
                        'trip_date' => $trip->getDepartureDay()->format('d/m/Y'),
                        'author_name' => $user->getFirstname() . ' ' . $user->getName(),
                        'rating' => $rating['rating_value'],
                        'comment' => $review->getContent(),
                        'status' => $review->getStatus()
                    ];
                }
            }

            echo json_encode($reviewsData);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des avis en attente: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Mettre à jour le statut d'un avis.
     */
    public function updateReviewStatus(): void
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            // Vérification du rôle (modérateur/admin)
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $roleId = $decodedToken->data->role;

            if ($roleId != 1 && $roleId != 2) {
                throw new Exception("Accès non autorisé.", 403);
            }

            // Récupération des données
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['review_id']) || !isset($data['status'])) {
                throw new Exception("Les champs 'review_id' et 'status' sont obligatoires.", 400);
            }

            $reviewId = $data['review_id'];
            $newStatus = $data['status'];

            // Validation du statut
            if (!in_array($newStatus, ['approved', 'rejected'])) {
                throw new Exception("Statut invalide.", 400);
            }

            // Mise à jour du statut dans la base de données
            if (Review::updateStatus($reviewId, $newStatus)) {
                
                // Si rejet, mail a l'utilisateur qui l'a soumisse
                if ($newStatus === 'rejected') {
                    $review = Review::find($reviewId);
                    if ($review) {
                        $user = User::find((int)$review->getUserId());
                        if ($user) {
                            $mailler = new Mailler();
                            $mailler->sendReviewRejectionMail($user->getEmail(), $user->getFirstname());
                        }
                    }
                }

                http_response_code(200);
                echo json_encode(["message" => "Statut de l'avis mis à jour avec succès."]);
            } else {
                throw new Exception("Erreur lors de la mise à jour du statut de l'avis.", 500);
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la mise à jour du statut de l'avis: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}
