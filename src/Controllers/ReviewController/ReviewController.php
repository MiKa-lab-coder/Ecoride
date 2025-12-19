<?php

namespace App\Controllers\ReviewController;

use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use App\Models\Booking;
use App\Services\Validator;
use App\services\TokenValidator;
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
        header('Application-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            // Récupération et validation du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $loggedInUserId = $decodedToken->sub;

            // Récupération des données depuis le corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides. Le corps de la requête doit être un JSON valide.", 400);
            }

            // Validation de la présence des champs
            if (!isset($data['trip_id']) || !isset($data['content'])) {
                throw new Exception("Les champs 'trip_id' et 'content' sont obligatoires.", 400);
            }

            $tripId = (int)$data['trip_id'];
            $content = $data['content'];

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

            // Récupération des réservations pour vérifier que l'utilisateur a bien participé au voyage
            // Note: La fonction getUserBookings devrait prendre un ID d'utilisateur comme argument.
            $bookings = Booking::getUserBookings($loggedInUserId);
            $hasBooked = false;
            foreach ($bookings as $booking) {
                if ($booking->getTripId() === $tripId) {
                    $hasBooked = true;
                    break;
                }
            }

            if (!$hasBooked) {
                throw new Exception("Vous ne pouvez pas commenter un voyage auquel vous n'avez pas participé.", 403);
            }

            // Enregistrement du commentaire
            $cleanContent = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
            $review = new Review(
                $loggedInUserId,
                $tripId,
                $cleanContent
            );

            if ($review->create()) {
                http_response_code(201);
                echo json_encode(["message" => "Commentaire enregistré avec succès."]);
                $this->logger->info("Commentaire soumis avec succès par l'utilisateur ID: " . $loggedInUserId .
                    " pour le trajet ID: " . $tripId);
            } else {
                throw new Exception("Erreur lors de l'enregistrement du commentaire.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la soumission du commentaire: " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}

