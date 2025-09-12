<?php

namespace App\Models;

use App\Models\Database\Database;
use DateTime;
use PDO;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Issues
 * Gère les problèmes signalés par les utilisateurs.
 */

class Issues extends BaseModel
{
    protected string $table = 'ISSUES';
    private int $issue_id; // ID du problème
    private string $status; // Statut du problème ('open', 'resolved')
    private DateTime $date_open;
    private string $description; // récuperation du commentaire sur MongoDB
    private int $user_id; // ID de l'utilisateur signalant le problème
    private int $trip_id; // ID du trajet concerné

    // Getters et setters
    public function getIssueId(): int
    {
        return $this->issue_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDateOpen(): DateTime
    {
        return $this->date_open;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getTripId(): int
    {
        return $this->trip_id;
    }

    public function setIssueId(int $issue_id): void
    {
        $this->issue_id = $issue_id;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setDateOpen(DateTime $date_open): void
    {
        $this->date_open = $date_open;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function setTripId(int $trip_id): void
    {
        $this->trip_id = $trip_id;
    }

    // Construct
    public function __construct(string $status, DateTime $date_open, string $description, int $user_id, int $trip_id)
    {
        parent::__construct();
        $this->status = $status;
        $this->date_open = $date_open;
        $this->description = $description;
        $this->user_id = $user_id;
        $this->trip_id = $trip_id;
    }

    // Méthode pour enregistrer un litige
    public function createIssues(): bool
    {
        try {
            $db = Database::getInstance();
            $openDate = $this->date_open->format('Y-m-d H:i:s');

            $stmt = $db->prepare("INSERT INTO {$this->table} (status, date_open, description, user_id, trip_id)
            VALUES (:status, :date_open, :description, :user_id, :trip_id)");
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':date_open', $this->date_open);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':trip_id', $this->trip_id);
            return $stmt->execute();
        } catch (Exception $e) {
            // Log the error
            $log = new Logger('Issues');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', Logger::ERROR));
            $log->error('Error creating issue: ' . $e->getMessage());
            return false;
        }
    }
}
