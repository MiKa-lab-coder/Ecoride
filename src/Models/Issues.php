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
    private string $description;
    private int $user_id; // ID de l'utilisateur signalant le problème (Plaignant)
    private int $trip_id; // ID du trajet concerné

    // Propriétés enrichies pour l'affichage (non stockées dans la table ISSUES)
    private ?string $trip_departure = null;
    private ?string $trip_arrival = null;
    private ?string $trip_date = null;
    private ?string $plaintiff_username = null;
    private ?string $plaintiff_email = null;
    private ?string $driver_username = null;
    private ?string $driver_email = null;


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

    // Getters pour les propriétés enrichies
    public function getTripDeparture(): ?string
    {
        return $this->trip_departure;
    }

    public function getTripArrival(): ?string
    {
        return $this->trip_arrival;
    }

    public function getTripDate(): ?string
    {
        return $this->trip_date;
    }

    public function getPlaintiffUsername(): ?string
    {
        return $this->plaintiff_username;
    }

    public function getPlaintiffEmail(): ?string
    {
        return $this->plaintiff_email;
    }

    public function getDriverUsername(): ?string
    {
        return $this->driver_username;
    }

    public function getDriverEmail(): ?string
    {
        return $this->driver_email;
    }

    // Setters pour les propriétés enrichies
    public function setTripDeparture(?string $trip_departure): void
    {
        $this->trip_departure = $trip_departure;
    }

    public function setTripArrival(?string $trip_arrival): void
    {
        $this->trip_arrival = $trip_arrival;
    }

    public function setTripDate(?string $trip_date): void
    {
        $this->trip_date = $trip_date;
    }

    public function setPlaintiffUsername(?string $plaintiff_username): void
    {
        $this->plaintiff_username = $plaintiff_username;
    }

    public function setPlaintiffEmail(?string $plaintiff_email): void
    {
        $this->plaintiff_email = $plaintiff_email;
    }

    public function setDriverUsername(?string $driver_username): void
    {
        $this->driver_username = $driver_username;
    }

    public function setDriverEmail(?string $driver_email): void
    {
        $this->driver_email = $driver_email;
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

            if (isset($this->issue_id)) {
                // Update
                $stmt = $db->prepare("UPDATE ISSUES SET status = :status WHERE issue_id = :issue_id");
                $stmt->bindParam(':status', $this->status);
                $stmt->bindParam(':issue_id', $this->issue_id);
            } else {
                // Insert
                $stmt = $db->prepare("INSERT INTO ISSUES (status, date_open, description, user_id, trip_id)
                VALUES (:status, :date_open, :description, :user_id, :trip_id)");
                $stmt->bindParam(':status', $this->status);
                $stmt->bindParam(':date_open', $openDate);
                $stmt->bindParam(':description', $this->description);
                $stmt->bindParam(':user_id', $this->user_id);
                $stmt->bindParam(':trip_id', $this->trip_id);
            }
            return $stmt->execute();
        } catch (Exception $e) {
            $log = new Logger('Issues');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error('Error saving issue: ' . $e->getMessage());
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
            $log = new Logger('Issues');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/app.log', 400));
            $log->error("Erreur de recuperation de l'issue : " . $e->getMessage());
            return null;
        }
    }

    // Méthode pour récupérer tous les litiges avec les détails enrichis
    public static function getAllIssues(): array
    {
        try {
            $db = Database::getInstance();
            // Jointure pour récupérer les infos du plaignant (u1), du trajet (t) et du conducteur (u2)
            $stmt = $db->prepare("
                SELECT 
                    i.*,
                    u1.username as plaintiff_username,
                    u1.email as plaintiff_email,
                    t.departure_location,
                    t.arrival_location,
                    t.departure_day,
                    u2.username as driver_username,
                    u2.email as driver_email
                FROM ISSUES i
                JOIN USERS u1 ON i.user_id = u1.user_id
                JOIN TRIPS t ON i.trip_id = t.trip_id
                JOIN USERS u2 ON t.driver_id = u2.user_id
                ORDER BY i.date_open DESC
            ");
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

                // Hydratation des données enrichies
                $issue->setPlaintiffUsername($result['plaintiff_username']);
                $issue->setPlaintiffEmail($result['plaintiff_email']);
                $issue->setTripDeparture($result['departure_location']);
                $issue->setTripArrival($result['arrival_location']);
                $issue->setTripDate($result['departure_day']);
                $issue->setDriverUsername($result['driver_username']);
                $issue->setDriverEmail($result['driver_email']);

                $issues[] = $issue;
            }
            return $issues;
        } catch (Exception $e) {
            $log = new Logger('Issues');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/app.log', 400));
            $log->error("Erreur de recuperation des issues : " . $e->getMessage());
            return [];
        }
    }
}