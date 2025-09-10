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
    private int $id;
    private int $rated_user_id; // ID de l'utilisateur noté (conducteur)
    private int $rater_user_id; // ID de l'utilisateur qui donne la note (passager)
    private int $trip_id; // ID du trajet associé à la note
    private int $rating_value; // Valeur de la note (1 à 5)


    public function __construct(?int $rated_user_id = null, ?int $rater_user_id = null, ?int $trip_id = null,
         ?int $rating_value = null, ?int $id = null)
    {
        parent::__construct();
        $this->rated_user_id = $rated_user_id ?? 0;
        $this->rater_user_id = $rater_user_id ?? 0;
        $this->trip_id = $trip_id ?? 0;
        $this->rating_value = $rating_value ?? 0;
        $this->id = $id ?? 0;
    }
    /**
     * Enregistre une nouvelle note dans la base de données.
     */
    public function save(): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('rating_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/rating.log', 100));
        try {
            $stmt = $db->prepare("INSERT INTO ratings (rated_user_id, rater_user_id, trip_id, rating_value)
            VALUES (:rated_user_id, :rater_user_id, :trip_id, :rating_value)");
            $stmt->bindParam(':rated_user_id', $this->rated_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':rater_user_id', $this->rater_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating_value', $this->rating_value, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $this->id = (int)$db->lastInsertId();
                $logger->info("Note donnée avec success ID: " . $this->id);
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
