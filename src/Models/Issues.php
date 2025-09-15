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
    public function save(): bool
    {
        try {
            $db = Database::getInstance();
            $openDate = $this->date_open->format('Y-m-d H:i:s');

            $stmt = $db->prepare("INSERT INTO ISSUES (status, date_open, description, user_id, trip_id)
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
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log',400));
            $log->error('Error creating issue: ' . $e->getMessage());
            return false;
        }
    }

    // Méthode pour récupérer un litige par son ID
    public static function findById(int $issue_id): ?Issues
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM ISSUES WHERE issue_id = :issue_id");
            $stmt->bindParam(':issue_id', $issue_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $issue = new Issues(
                    $row['status'],
                    new DateTime($row['date_open']),
                    $row['description'],
                    (int)$row['user_id'],
                    (int)$row['trip_id']
                );
                $issue->setIssueId((int)$row['issue_id']);
                return $issue;
            }
            return null;
        } catch (Exception $e) {
            // Log the error
            $log = new Logger('Issues');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("Erreur de recuperation de l'issue : " . $e->getMessage());
            return null;
        }
    }
    // Méthode pour récupérer tous les litiges
    public static function getAllIssues(): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM ISSUES ORDER BY date_open DESC ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $issues = [];
            foreach ($results as $result) {
                $issue = new Issues(
                    $result['status'],
                    new DateTime($result['date_open']),
                    $result['description'],
                    (int)$result['user_id'],
                    (int)$result['trip_id']
                );
                $issue->setIssueId((int)$result['issue_id']);
                $issues[] = $issue;
            }
            return $issues;
        } catch (Exception $e) {
            $log = new Logger('Issues');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("Erreur de recuperation des issues : " . $e->getMessage());
            return [];
        }
    }
}
