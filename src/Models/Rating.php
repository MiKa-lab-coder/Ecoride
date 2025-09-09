<?php
namespace App\Models;

use PDO;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Models\Database\Database;
use App\Models\User;
use PDOException;


/**
 * Class Rating
 * Gère les notes des conducteurs.
 */
class Rating
{
    protected string $table = 'ratings';

    public function __construct(
        protected ?int $rated_user_id = null,
        protected ?int $rater_user_id = null,
        protected ?int $trip_id = null,
        protected ?int $rating_value = null,
        protected ?int $id = null,
    )
    {
    }
    /**
     * Enregistre une nouvelle note dans la base de données.
     */
    public function save(): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('rating_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/rating.log', Logger::INFO));
        try {
            $stmt = $db->prepare("INSERT INTO ratings (rated_user_id, rater_user_id, trip_id, rating_value)
            VALUES (:rated_user_id, :rater_user_id, :trip_id, :rating_value)");
            $stmt->bindParam(':rated_user_id', $this->rated_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':rater_user_id', $this->rater_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating_value', $this->rating_value, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $this->id = (int)$db->lastInsertId();
                $logger->info("Rating saved successfully with ID: " . $this->id);
                return true;
            } else {
                $logger->error("Failed to save rating.");
                return false;
            }
        } catch (Exception $e) {
            $logger->error("Error saving rating: " . $e->getMessage());
            throw new Exception("Error saving rating: " . $e->getMessage());
        }
    }
// Recuperer la note moyenne d'un conducteur
    public static function getAverageRatingForUser(int $userId): float
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare('SELECT AVG(rating_value) AS average_rating FROM ratings WHERE rated_user_id = :userId');
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['average_rating'] ?? 0);
        } catch (Exception $e) {
            $logger = new Logger('rating_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', Logger::ERROR));
            $logger->error('Erreur lors du calcul de la note moyenne: ' . $e->getMessage());
            return 0;
        }
    }
}
