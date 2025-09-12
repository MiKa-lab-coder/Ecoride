<?php

namespace App\Models;

use App\Models\Database\Database;
use DateTime;
use PDO;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Booking
 * Gère les réservations de trajets.
 */
class Booking extends BaseModel
{
    protected string $table = 'BOOKINGS';
    private int $booking_id; // ID de la réservation
    private DateTime $booking_date; // Date de la réservation
    private int $user_id; // ID de l'utilisateur effectuant la réservation
    private int $trip_id; // ID du trajet réservé

    // Getters et setters
    public function getBookingId(): int
    {
        return $this->booking_id;
    }
    public function getBookingDate(): DateTime
    {
        return $this->booking_date;
    }
    public function getUserId(): int
    {
        return $this->user_id;
    }
    public function getTripId(): int
    {
        return $this->trip_id;
    }
    public function setBookingId(int $booking_id): void
    {
        $this->booking_id = $booking_id;
    }
    public function setBookingDate(DateTime $booking_date): void
    {
        $this->booking_date = $booking_date;
    }
    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }
    public function setTripId(int $trip_id): void
    {
        $this->trip_id = $trip_id;
    }

    public function __construct(DateTime $booking_date, int $user_id, int $trip_id)
    {
        parent::__construct();
        $this->booking_date = $booking_date;
        $this->user_id = $user_id;
        $this->trip_id = $trip_id;
    }

    // Méthode pour créer une réservation
    public  function create(): bool
    {
        $logger = new Logger('booking_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/booking.log', 100));

        try {
            $db = Database::getInstance();
            $dateformat = $this->booking_date->format('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO BOOKINGS (booking_date, user_id, trip_id) 
                              VALUES (:booking_date, :user_id, :trip_id)");
            $stmt->bindParam(':booking_date', $this->$dateformat);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':trip_id', $this->trip_id);
            $result = $stmt->execute();
            if ($result) {
                $this->booking_id = (int)$db->lastInsertId();
                $logger->info("Réservation créée avec succès. ID: {$this->booking_id}");
            }
            return $result;
        } catch (Exception $e) {
            $logger->error("Erreur lors de la création de la réservation: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour annuler une réservation
    public function cancel(): bool
    {
        $logger = new Logger('booking_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/booking.log', 100));
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM BOOKINGS WHERE booking_id = :booking_id");
            $stmt->bindParam(':booking_id', $this->booking_id);
            $result = $stmt->execute();
            if ($result) {
                $logger->info("Réservation annulée avec succès. ID: {$this->booking_id}");
            }
            return $result;
        } catch (Exception $e) {
            $logger->error("Erreur lors de l'annulation de la réservation: " . $e->getMessage());
            return false;
        }
    }
    // Méthode pour récupérer les réservations d'un utilisateur pour affichage dans son profil
    public static function getUserBookings(int $user_id): array
    {
        $logger = new Logger('booking_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/booking.log', 100));
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM BOOKINGS WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $bookings = [];
            foreach ($results as $row) {
                $booking = new Booking(new DateTime($row['booking_date']), (int)$row['user_id'], (int)$row['trip_id']);
                $booking->setBookingId((int)$row['booking_id']);
                $bookings[] = $booking;
            }
            return $bookings;
        } catch (Exception $e) {
            $logger->error("Erreur lors de la récupération des réservations de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }
    // Méthode pour récupérer une réservation par son ID
    public static function findById(int $booking_id): ?Booking
    {
        $logger = new Logger('booking_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/booking.log', 100));
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM BOOKINGS WHERE booking_id = :booking_id");
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $booking = new Booking(new DateTime($row['booking_date']), (int)$row['user_id'], (int)$row['trip_id']);
                $booking->setBookingId((int)$row['booking_id']);
                return $booking;
            } else {
                return null;
            }
        } catch (Exception $e) {
            $logger->error("Erreur lors de la récupération de la réservation ID: $booking_id - " . $e->getMessage());
            return null;
        }
    }
}


