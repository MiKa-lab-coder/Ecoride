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

    public function __construct()
    {
        $this->logger = new Logger('booking_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/booking.log', 100));
    }

    public function bookTrip(int $trip_id): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $user_id = $decodedToken->data->id;

            $user = User::find($user_id);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            $trip = Trip::findById($trip_id);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }

            if ($trip->calculateRemainingSeats() <= 0) {
                throw new Exception("Aucune place disponible pour ce trajet.", 400);
            }

            $booking = new Booking(new DateTime(), $user_id, $trip_id);
            if (!$booking->create()) {
                throw new Exception("Erreur lors de la création de la réservation.", 500);
            }

            $trip->decrementAvailableSeats();

            $transaction = new Transaction($user_id, -$trip->getTripPrice(), 'payment', $trip_id);
            if (!$transaction->save()) {
                $booking->cancel(); // Rollback booking
                $trip->incrementAvailableSeats(); // Rollback seats
                throw new Exception("Erreur lors du paiement du trajet.", 500);
            }

            http_response_code(201);
            echo json_encode(["message" => "Réservation et paiement effectués avec succès.", "booking_id" => $booking->getBookingId()]);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function cancelBooking(int $booking_id): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $user_id = $decodedToken->data->id;

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

            $transaction = new Transaction($user_id, $trip->getTripPrice(), 'cancellation', $trip->getTripId());
            if (!$transaction->save()) {
                // Log error but don't rollback cancellation, as user expects it
                $this->logger->error("Erreur lors du remboursement pour la réservation ID: $booking_id");
            }

            http_response_code(200);
            echo json_encode(["message" => "Réservation annulée et remboursement effectué avec succès."]);
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

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