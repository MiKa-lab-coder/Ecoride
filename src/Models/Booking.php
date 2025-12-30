<?php

namespace App\Models;

use App\Models\Database\Database;
use DateTime;
use PDO;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Booking extends BaseModel
{
    protected string $table = 'BOOKINGS';
    private int $booking_id;
    private DateTime $booking_date;
    private int $user_id;
    private int $trip_id;

    // Getters et setters
    public function getBookingId(): int { return $this->booking_id; }
    public function getBookingDate(): DateTime { return $this->booking_date; }
    public function getUserId(): int { return $this->user_id; }
    public function getTripId(): int { return $this->trip_id; }
    public function setBookingId(int $booking_id): void { $this->booking_id = $booking_id; }
    public function setBookingDate(DateTime $booking_date): void { $this->booking_date = $booking_date; }
    public function setUserId(int $user_id): void { $this->user_id = $user_id; }
    public function setTripId(int $trip_id): void { $this->trip_id = $trip_id; }

    public function __construct(DateTime $booking_date, int $user_id, int $trip_id)
    {
        parent::__construct();
        $this->booking_date = $booking_date;
        $this->user_id = $user_id;
        $this->trip_id = $trip_id;
    }

    /**
     * Création d'une réservation
     */
    public function create(): bool
    {
        $logger = new Logger('booking_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/booking.log', 100));
        try {
            $db = Database::getInstance();
            $dateformat = $this->booking_date->format('Y-m-d H:i:s');
            $stmt = $db->prepare("INSERT INTO BOOKINGS (booking_date, user_id, trip_id) VALUES (:booking_date, :user_id, :trip_id)");
            $stmt->bindParam(':booking_date', $dateformat);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':trip_id', $this->trip_id);
            $result = $stmt->execute();
            if ($result) {
                $this->booking_id = (int)$db->lastInsertId();
            }
            return $result;
        } catch (Exception $e) {
            $logger->error("Erreur lors de la création de la réservation: " . $e->getMessage());
            return false;
        }
    }
    /**
    * Annulation d'une réservation
    */
    public function cancel(): bool
    {
        $logger = new Logger('booking_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/booking.log', 100));
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("DELETE FROM BOOKINGS WHERE booking_id = :booking_id");
            $stmt->bindParam(':booking_id', $this->booking_id);
            return $stmt->execute();
        } catch (Exception $e) {
            $logger->error("Erreur lors de l'annulation de la réservation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupération d'une réservation par son ID
     */
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
            }
            return null;
        } catch (Exception $e) {
            $logger->error("Erreur lors de la récupération de la réservation ID: $booking_id - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupération des réservations d'un utilisateur
     */
    public static function getPassengersForTrip(int $tripId): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                SELECT u.user_id, u.firstname, u.name 
                FROM USERS u
                JOIN BOOKINGS b ON u.user_id = b.user_id
                WHERE b.trip_id = :tripId
            ");
            $stmt->bindParam(':tripId', $tripId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupération des réservations d'un utilisateur avec les détails du trajet
     */
    public static function getUserBookingsWithDetails(int $user_id): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                SELECT 
                    b.booking_id,
                    t.trip_id,
                    t.departure_location,
                    t.arrival_location,
                    t.departure_day,
                    t.departure_time,
                    t.trip_price,
                    u.name as driver_name,
                    u.firstname as driver_firstname
                FROM BOOKINGS b
                JOIN TRIPS t ON b.trip_id = t.trip_id
                JOIN USERS u ON t.driver_id = u.user_id
                WHERE b.user_id = :user_id AND t.status != 'completed'
            ");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Vérifie si un utilisateur a une réservation pour un trajet donné.
     */
    public static function hasUserBookedTrip(int $userId, int $tripId): bool
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM BOOKINGS WHERE user_id = :userId AND trip_id = :tripId");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':tripId', $tripId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère toutes les réservations pour un trajet donné.
     */
    public static function findByTripId(int $tripId): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT * FROM BOOKINGS WHERE trip_id = :tripId");
            $stmt->bindParam(':tripId', $tripId, PDO::PARAM_INT);
            $stmt->execute();
            
            $bookings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $booking = new Booking(new DateTime($row['booking_date']), (int)$row['user_id'], (int)$row['trip_id']);
                $booking->setBookingId((int)$row['booking_id']);
                $bookings[] = $booking;
            }
            return $bookings;
        } catch (PDOException $e) {
            return [];
        }
    }
}