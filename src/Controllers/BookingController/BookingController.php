<?php

namespace App\Controllers\BookingController;

use App\Models\Booking;
use App\Models\Trip;
use App\Models\User;
use App\Models\Transaction;
use App\Services\TokenValidator;
use DateTime;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PDO;

/**
 * Class BookingController
 * Gère les opérations liées aux réservations de trajets, Comptage des places disponibles.
 * Les transactions sont gérées par la classe Transaction.(payTrip, payBackTrip),
 */

class BookingController
{
    private Logger $logger;

    public function __construct()
    {
        // Initialisation du logger
        $this->logger = new Logger('booking_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/booking.log', 100));
    }

    // Méthode pour réserver un trajet
    public function bookTrip(int $user_id, int $trip_id): bool
    {

        header('Content-Type: application/json');
        // Récupération du token JWT depuis les en-têtes de la requête
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        try {
            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation
            if ($decodedToken->data->id !== $user_id) {
                $this->logger->warning("Tentative d'accès non autorisé au profil de l'utilisateur ID: $user_id");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vérification de l'existence de l'utilisateur
            $user = User::find($user_id);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé ID: $user_id");
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Vérification de l'existence du trajet
            $trip = Trip::findById($trip_id);
            if (!$trip) {
                $this->logger->error("Trajet non trouvé ID: $trip_id");
                throw new Exception("Trajet non trouvé.", 404);
            }
            // Vérification des places disponibles
            if ($trip->calculateRemainingSeats() <= 0) {
                $this->logger->info("Aucune place disponible pour le trajet ID: $trip_id");
                throw new Exception("Aucune place disponible pour ce trajet.", 400);
            }
            // Création de la réservation
            $booking = new Booking(new DateTime(), $user_id, $trip_id);
            if (!$booking->create()) {
                $this->logger->error("Erreur lors de la création de la réservation pour l'utilisateur
                 ID: $user_id et le trajet ID: $trip_id");
                throw new Exception("Erreur lors de la création de la réservation.", 500);
            }
            // Mise à jour des places disponibles du trajet
            if (!$trip->decrementAvailableSeats()) {
                $this->logger->error("Erreur lors de la mise à jour des places disponibles pour le trajet ID: $trip_id");
                // En cas d'erreur de mise à jour des places, on annule la réservation
                $booking->cancel();
                throw new Exception("Erreur lors de la mise à jour des places disponibles du trajet.", 500);
            }

            // La reservation vaux paiement, on déclenche la transaction
            $transaction = new Transaction(
                $user_id,
                -$trip->getTripPrice(),
                'payment',
                $trip_id
            );
            if (!$transaction->save()) {
                $this->logger->error("Erreur lors de l'enregistrement de la transaction pour l'utilisateur
                 ID: $user_id et le trajet ID: $trip_id");
                // En cas d'erreur de transaction, on annule la réservation
                $booking->cancel();
                throw new Exception("Erreur lors du paiement du trajet.", 500);
            }
            // Tout s'est bien passé
            http_response_code(201);
            echo json_encode(["message" => "Réservation et paiement effectués avec succès.", "booking_id" => $booking->getBookingId()]);
            $this->logger->info("Réservation et paiement effectués avec succès pour l'utilisateur
             ID: $user_id et le trajet ID: $trip_id");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la réservation du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
        }
    }
    // Méthode pour annuler une réservation (suppression/mise à jour du trajet avec des places en moins par le conducteur)
    public function cancelBooking(int $user_id, int $booking_id, $trip_id): bool
    {
        header('Content-Type: application/json');
        // Récupération du token JWT depuis les en-têtes de la requête
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        try {
            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation
            if ($decodedToken->data->id !== $user_id) {
                $this->logger->warning("Tentative d'accès non autorisé au profil de l'utilisateur ID: $user_id");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vérification de l'existence de l'utilisateur
            $user = User::find($user_id);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé ID: $user_id");
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Vérification de l'existence de la réservation
            $booking = Booking::findById($booking_id);
            if (!$booking) {
                $this->logger->error("Réservation non trouvée ID: $booking_id");
                throw new Exception("Réservation non trouvée.", 404);
            }
            // Vérification que la réservation appartient bien à l'utilisateur
            if ($booking->getUserId() !== $user_id) {
                $this->logger->warning("Tentative d'annulation non autorisée de la réservation ID: $booking_id
                 par l'utilisateur ID: $user_id");
                throw new Exception("Vous n'êtes pas autorisé à annuler cette réservation.", 403);
            }
            // Récupération du trajet pour le montant du remboursement
            $trip = Trip::findById($booking->getTripId());
            if (!$trip) {
                $this->logger->error("Trajet non trouvé pour la réservation ID: $booking_id");
                throw new Exception("Trajet associé à la réservation non trouvé.", 404);
            }
            // Annulation de la réservation
            if (!$booking->cancel()) {
                $this->logger->error("Erreur lors de l'annulation de la réservation ID: $booking_id");
                throw new Exception("Erreur lors de l'annulation de la réservation.", 500);
            }
            // Mise à jour des places disponibles du trajet
            if (!$trip->incrementAvailableSeats()) {
                $this->logger->error("Erreur lors de la mise à jour des places disponibles pour le trajet ID: {$trip->getTripId()}");
                throw new Exception("Erreur lors de la mise à jour des places disponibles du trajet.", 500);
            }
            // La reservation annulée vaux remboursement, on déclenche la transaction
            $transaction = new Transaction(
                $user_id,
                $trip->getTripPrice(),
                'cancellation',
                $trip->getTripId()
            );
            if (!$transaction->save()) {
                $this->logger->error("Erreur lors de l'enregistrement de la transaction de remboursement pour l'utilisateur
                 ID: $user_id et le trajet ID: $trip_id");
                throw new Exception("Erreur lors du remboursement du trajet.", 500);
            }
            // Tout s'est bien passé
            http_response_code(200);
            echo json_encode(["message" => "Réservation annulée et remboursement effectué avec succès.", "booking_id" => $booking_id]);
            $this->logger->info("Réservation annulée et remboursement effectué avec succès pour l'utilisateur
             ID: $user_id et la réservation ID: $booking_id");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'annulation de la réservation: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
        }
    }
    // Méthode pour récupérer les réservations d'un utilisateur pour affichage dans son profil
    public function getUserBookings(int $user_id): array
    {
        header('Content-Type: application/json');
        // Récupération du token JWT depuis les en-têtes de la requête
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        try {
            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            // Vérification de l'autorisation
            if ($decodedToken->data->id !== $user_id) {
                $this->logger->warning("Tentative d'accès non autorisé au profil de l'utilisateur ID: $user_id");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vérification de l'existence de l'utilisateur
            $user = User::find($user_id);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé ID: $user_id");
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Récupération des réservations de l'utilisateur
            $bookings = Booking::getUserBookings($user_id);
            http_response_code(200);
            echo json_encode($bookings);
            $this->logger->info("Récupération des réservations pour l'utilisateur ID: $user_id");
            return $bookings;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des réservations de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            return [];
        }
    }
}