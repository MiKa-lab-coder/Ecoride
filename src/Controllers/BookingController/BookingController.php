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

class BookingController
{
    private Logger $logger;
    private const PLATFORM_FEE = 2;

    public function __construct()
    {
        $this->logger = new Logger('booking_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/booking.log', 100));
    }

    /**
     * Réserver un trajet
     */
    public function bookTrip(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $user_id = $decodedToken->data->id;

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['trip_id'])) {
                throw new Exception("ID du trajet manquant.", 400);
            }
            $trip_id = (int)$data['trip_id'];

            $user = User::find($user_id);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            $trip = Trip::findById($trip_id);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }

            // Vérification du solde de l'utilisateur
            $userBalance = Transaction::getUserBalance($user_id);
            if ($userBalance < $trip->getTripPrice()) {
                throw new Exception("Crédits insuffisants pour réserver ce trajet.", 402); // 402 Payment Required
            }

            if ($trip->calculateRemainingSeats() <= 0) {
                throw new Exception("Aucune place disponible pour ce trajet.", 400);
            }

            $booking = new Booking(new DateTime(), $user_id, $trip_id);
            if (!$booking->create()) {
                throw new Exception("Erreur lors de la création de la réservation.", 500);
            }

            $trip->decrementAvailableSeats();

            // Transaction 1: Débit du passager
            $passengerTransaction = new Transaction($user_id, -$trip->getTripPrice(), 'payment', $trip_id);
            if (!$passengerTransaction->save()) {
                $booking->cancel(); // Rollback booking
                $trip->incrementAvailableSeats(); // Rollback seats
                throw new Exception("Erreur lors du paiement du trajet.", 500);
            }

            // Transaction 2: Crédit du conducteur
            $driverAmount = $trip->getTripPrice() - self::PLATFORM_FEE;
            $driverTransaction = new Transaction($trip->getDriverId(), $driverAmount, 'payment', $trip_id);
            $driverTransaction->save();

            // Transaction 3: Crédit de la plateforme
            $platformTransaction = new Transaction(1, self::PLATFORM_FEE, 'service_fee', $trip_id);
            $platformTransaction->save();

            http_response_code(201);
            echo json_encode(["message" => "Réservation et paiement effectués avec succès.", "booking_id" => $booking->getBookingId()]);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Annuler une réservation
     */
    public function cancelBooking(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $user_id = $decodedToken->data->id;

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['booking_id'])) {
                throw new Exception("ID de réservation manquant.", 400);
            }
            $booking_id = (int)$data['booking_id'];

            $booking = Booking::findById($booking_id);
            if (!$booking) {
                throw new Exception("Réservation non trouvée.", 404);
            }

            if ($booking->getUserId() !== $user_id) {
                throw new Exception("Vous n'êtes pas autorisé à annuler cette réservation.", 403);
            }

            $trip = Trip::findById($booking->getTripId());
            if (!$trip) {
                throw new Exception("Trajet associé à la réservation non trouvé.", 404);
            }

            if (!$booking->cancel()) {
                throw new Exception("Erreur lors de l'annulation de la réservation.", 500);
            }

            $trip->incrementAvailableSeats();

            // Transaction 1: Remboursement du passager
            $passengerTransaction = new Transaction($user_id, $trip->getTripPrice(), 'cancellation', $trip->getTripId());
            $passengerTransaction->save();

            // Transaction 2: Débit du conducteur
            $driverAmount = $trip->getTripPrice() - self::PLATFORM_FEE;
            $driverTransaction = new Transaction($trip->getDriverId(), -$driverAmount, 'cancellation', $trip->getTripId());
            $driverTransaction->save();

            // Transaction 3: Débit de la plateforme
            $platformTransaction = new Transaction(1, -self::PLATFORM_FEE, 'cancellation', $trip->getTripId());
            $platformTransaction->save();

            http_response_code(200);
            echo json_encode(["message" => "Réservation annulée et remboursement effectué avec succès."]);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Récupération des réservations d'un utilisateur
     */
    public function getUserBookings(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $user_id = $decodedToken->data->id;

            $bookings = Booking::getUserBookingsWithDetails($user_id);

            http_response_code(200);
            echo json_encode($bookings);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }
}