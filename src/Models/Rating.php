<?php

namespace App\Models;

use PDO;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Models\Database\Database;

class Rating extends BaseModel
{
    protected string $table = 'RATINGS';
    private int $rating_id;
    private int $rated_user_id;
    private int $passenger_id;
    private int $trip_id;
    private int $rating_value;

    // Getters et setters
    public function getRatingId(): int
    {
        return $this->rating_id;
    }

    public function getRatedUserId(): int
    {
        return $this->rated_user_id;
    }

    public function getPassengerId(): int
    {
        return $this->passenger_id;
    }

    public function getTripId(): int
    {
        return $this->trip_id;
    }

    public function getRatingValue(): int
    {
        return $this->rating_value;
    }

    public function setRatingValue(int $rating_value): void
    {
        $this->rating_value = $rating_value;
    }

    public function __construct(int $rated_user_id, int $passenger_id, int $trip_id, int $rating_value)
    {
        parent::__construct();
        $this->rated_user_id = $rated_user_id;
        $this->passenger_id = $passenger_id;
        $this->trip_id = $trip_id;
        $this->rating_value = $rating_value;
    }

    public function save(): bool
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("INSERT INTO RATINGS (rated_user_id, passenger_id, trip_id, rating_value)
                                        VALUES (:rated_user_id, :passenger_id, :trip_id, :rating_value)");
            $stmt->bindParam(':rated_user_id', $this->rated_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':passenger_id', $this->passenger_id, PDO::PARAM_INT);
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating_value', $this->rating_value, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public static function getAverageRatingForUser(int $userId): float
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare('SELECT AVG(rating_value) AS average_rating FROM RATINGS WHERE rated_user_id = :userId');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['average_rating'] ?? 0);
        } catch (Exception $e) {
            return 0.0;
        }
    }

    public static function hasUserRatedTrip(int $raterId, int $tripId, int $ratedId): bool
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM RATINGS WHERE passenger_id = :raterId AND
                                   trip_id = :tripId AND rated_user_id = :ratedId");
            $stmt->bindParam(':raterId', $raterId, PDO::PARAM_INT);
            $stmt->bindParam(':tripId', $tripId, PDO::PARAM_INT);
            $stmt->bindParam(':ratedId', $ratedId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Récupère toutes les évaluations reçues par un utilisateur.
     */
    public static function getRatingsForUser(int $userId): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT * FROM RATINGS WHERE rated_user_id = :userId ORDER BY id DESC");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}
