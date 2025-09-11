<?php
namespace App\Models;


use PDO;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Models\Database\Database;


/**
 * Class Rating
 * Gère les notes des conducteurs.
 */
class Rating extends BaseModel
{
    protected string $table = 'RATINGS';
    private int $rating_id; // ID de la note
    private int $rated_user_id; // ID de l'utilisateur noté (conducteur)
    private int $passenger_id; // ID de l'utilisateur qui donne la note (passager)
    private int $trip_id; // ID du trajet associé à la note
    private int $rating_value; // Valeur de la note

    //getter et setters
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

    public function __construct(int $rated_user_id, int $passenger_id, int $trip_id,
         int $rating_value , int $rating_id )
    {
        parent::__construct();
        $this->rated_user_id = $rated_user_id ;
        $this->passenger_id = $passenger_id ;
        $this->trip_id = $trip_id ;
        $this->rating_value = $rating_value ;
        $this->rating_id = $rating_id ;
    }

    public static function hydrate(array $data): self
    {
       $rating = new self(
            $data['rated_user_id'],
            $data['passenger_id'],
            $data['trip_id'],
            $data['rating_value'],
            $data['rating_id']
        );
       $rating->rating_id = $data['rating_id'];
       return $rating;
    }



    public function save(): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('rating_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/rating.log', 100));
        try {
            $stmt = $db->prepare("INSERT INTO RATINGS (rated_user_id, passenger_id, trip_id, rating_value)
            VALUES (:rated_user_id, :passenger_id, :trip_id, :rating_value)");
            $stmt->bindParam(':rated_user_id', $this->rated_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':passenger_id', $this->passenger_id, PDO::PARAM_INT);
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating_value', $this->rating_value, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $this->rating_id = (int)$db->lastInsertId();
                $logger->info("Note donnée avec success ID: " . $this->rating_id);
                return true;
            } else {
                $logger->error("Échec d'enregistrement de la note.");
                return false;
            }
        } catch (Exception $e) {
            $logger->error("Erreur de note: " . $e->getMessage());
            throw new Exception("Erreur de : " . $e->getMessage());
        }
    }
// Recuperer la note moyenne d'un conducteur
    public static function getAverageRatingForUser(int $userId): float
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare('SELECT AVG(rating_value) AS average_rating FROM RATINGS WHERE rated_user_id = :userId');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $average = (float)($result['average_rating'] ?? 0);
            return round($average, 2);
        } catch (Exception $e) {
            $logger = new Logger('rating_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
            $logger->error('Erreur lors du calcul de la note moyenne: ' . $e->getMessage());
            return 0.0;
        }
    }
    // Verifier si un passager a déjà noté un conducteur pour un trajet précis
    public static function hasPassengerRatedTrip(int $passengerId, int $tripId): bool
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare('SELECT COUNT(*) FROM RATINGS WHERE passenger_id = :passengerId AND trip_id = :tripId');
            $stmt->bindParam(':passengerId', $passengerId, PDO::PARAM_INT);
            $stmt->bindParam(':tripId', $tripId, PDO::PARAM_INT);
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
            return $count > 0;
        } catch (Exception $e) {
            $logger = new Logger('rating_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
            $logger->error('Erreur lors de la vérification de la note: ' . $e->getMessage());
            return false;
        }
    }
}
