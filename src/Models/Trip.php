<?php

namespace App\Models;

use App\Models\Database\Database;
use DateTime;
use PDO;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Classe Trip
 * Représente un voyage avec ses attributs et méthodes associées.
 */
class Trip extends BaseModel
{

    protected string $table = 'trips';
    private ?int $id_trip;
    private string $trip_name;
    private string $trip_description;
    private string $departure_location;
    private string $arrival_location;
    private DateTime $departure_date_time; // Modifié
    private DateTime $arrival_date_time;   // Modifié
    private int $trip_price;
    private int $seats_available;
    private bool $pet_allowed;
    private bool $smoking_allowed;
    private int $id_car;
    private int $id_user;
    private string $status;

    // Getters
    public function getIdTrip(): ?int
    {
        return $this->id_trip;
    }

    public function getTripName(): string
    {
        return $this->trip_name;
    }

    public function getTripDescription(): string
    {
        return $this->trip_description;
    }

    public function getDepartureLocation(): string
    {
        return $this->departure_location;
    }

    public function getArrivalLocation(): string
    {
        return $this->arrival_location;
    }

    public function getDepartureDateTime(): DateTime
    {
        return $this->departure_date_time;
    }

    public function getArrivalDateTime(): DateTime
    {
        return $this->arrival_date_time;
    }

    public function getTripPrice(): int
    {
        return $this->trip_price;
    }

    public function getSeatsAvailable(): int
    {
        return $this->seats_available;
    }

    public function isPetAllowed(): bool
    {
        return $this->pet_allowed;
    }

    public function isSmokingAllowed(): bool
    {
        return $this->smoking_allowed;
    }

    public function getIdCar(): int
    {
        return $this->id_car;
    }

    public function getIdUser(): int
    {
        return $this->id_user;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    // Setters
    public function setTripName(string $trip_name): void
    {
        $this->trip_name = $trip_name;
    }

    public function setTripDescription(string $trip_description): void
    {
        $this->trip_description = $trip_description;
    }

    public function setDepartureLocation(string $departure_location): void
    {
        $this->departure_location = $departure_location;
    }

    public function setArrivalLocation(string $arrival_location): void
    {
        $this->arrival_location = $arrival_location;
    }

    public function setDepartureDateTime(DateTime $departure_date_time): void
    {
        $this->departure_date_time = $departure_date_time;
    }

    public function setArrivalDateTime(DateTime $arrival_date_time): void
    {
        $this->arrival_date_time = $arrival_date_time;
    }

    public function setTripPrice(int $trip_price): void
    {
        $this->trip_price = $trip_price;
    }

    public function setSeatsAvailable(int $seats_available): void
    {
        $this->seats_available = $seats_available;
    }

    public function setPetAllowed(bool $pet_allowed): void
    {
        $this->pet_allowed = $pet_allowed;
    }

    public function setSmokingAllowed(bool $smoking_allowed): void
    {
        $this->smoking_allowed = $smoking_allowed;
    }

    public function setIdCar(int $id_car): void
    {
        $this->id_car = $id_car;
    }
    public function setIdUser(int $id_user): void
    {
        $this->id_user = $id_user;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    // Constructor
    public function __construct(
        string   $trip_name,
        string   $trip_description,
        string   $departure_location,
        string   $arrival_location,
        DateTime $departure_date_time,
        DateTime $arrival_date_time,
        int      $trip_price,
        int      $seats_available,
        bool     $pet_allowed,
        bool     $smoking_allowed,
        int      $id_car,
        int      $id_user,
        string   $status = 'pending' // Statut par défaut
    )
    {
        parent::__construct();
        // On initialise pas id_trip car il est auto-incrémenté
        $this->setTripName($trip_name);
        $this->setTripDescription($trip_description);
        $this->setDepartureLocation($departure_location);
        $this->setArrivalLocation($arrival_location);
        $this->setDepartureDateTime($departure_date_time);
        $this->setArrivalDateTime($arrival_date_time);
        $this->setTripPrice($trip_price);
        $this->setSeatsAvailable($seats_available);
        $this->setPetAllowed($pet_allowed);
        $this->setSmokingAllowed($smoking_allowed);
        $this->setIdCar($id_car);
        $this->setIdUser($id_user);
        $this->setStatus($status);
    }

    // Méthode pour hydrater l'objet Trip
    public static function hydrate(array $data): Trip
    {
        $trip = new Trip(
            $data['trip_name'],
            $data['trip_description'],
            $data['departure_location'],
            $data['arrival_location'],
            new DateTime($data['departure_date_time']),
            new DateTime($data['arrival_date_time']),
            (int)$data['trip_price'],
            (int)$data['seats_available'],
            (bool)$data['pet_allowed'],
            (bool)$data['smoking_allowed'],
            (int)$data['id_car'],
            (int)$data['id_user'],
            $data['status'] ?? 'pending' // Statut par défaut si non fourni
        );
        $trip->id_trip = isset($data['id_trip']) ? (int)$data['id_trip'] : null;

        return $trip;
    }

    // Méthode pour verifier la presence d'un trajet
    public static function findById(int $id_trip): ?Trip
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM trips WHERE id_trip = :id_trip');
        $stmt->bindParam(':id_trip', $id_trip, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            } else {
                return null;
            }
        } catch (PDOException $e) {
            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la récupération du trajet par ID: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    // Méthode pour récupérer un trajet par nom du trajet
    public static function getTripByName(string $trip_name): ?Trip
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM trips WHERE trip_name = :trip_name');
        $stmt->bindParam(':trip_name', $trip_name, PDO::PARAM_STR);
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            } else {
                return null;
            }
        } catch (PDOException $e) {

            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la récupération du trajet par nom: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    // Méthode pour trouver des trajets par dates depart
    public static function getTripByDate(DateTime $date): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM trips WHERE DATE(departure_date_time) = DATE(:date)
                    ORDER BY departure_date_time ASC');
        $stmt->bindValue(':date', $date->format('Y-m-d'), PDO::PARAM_STR);
        try {
            $stmt->execute();
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $data) {
                $trips[] = self::hydrate($data);
            }
            return $trips;
        } catch (PDOException $e) {

            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la récupération des trajets par date: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    // Méthode pour trouver des trajets par heure de depart
    public static function getTripsByDepartTime(DateTime $date): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM trips WHERE DATE(departure_date_time) = :date ORDER BY departure_date_time ASC');
        $stmt->bindValue(':date', $date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        try {
            $stmt->execute();
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $data) {
                $trips[] = self::hydrate($data);
            }
            return $trips;
        } catch (PDOException $e) {
            // Log the error message
            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la récupération des trajets par heure de départ: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }
    // Méthode pour récupérer les trajets par avec un statut 'pending'
    public static function findByStatus(): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM trips WHERE status = 'pending' ORDER BY departure_date_time ASC");
        try {
            $stmt->execute();
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $data) {
                $trips[] = self::hydrate($data);
            }
            return $trips;
        } catch (PDOException $e) {

            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la récupération des trajets par statut: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }
    // Méthode pour récupérer tous les trajets d'un utilisateur
    public static function findByUser(int $id_user): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM trips WHERE id_user = :id_user ORDER BY departure_date_time ASC');
        $stmt->bindParam(':id_user', $id_user, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $data) {
                $trips[] = self::hydrate($data);
            }
            return $trips;
        } catch (PDOException $e) {

            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la récupération des trajets par utilisateur: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    // Méthode pour créer un nouveau trajet
    public function save(): bool
    {
        $db = Database::getInstance();

        try {
            $data = [
                'trip_name' => $this->getTripName(),
                'trip_description' => $this->getTripDescription(),
                'departure_location' => $this->getDepartureLocation(),
                'arrival_location' => $this->getArrivalLocation(),
                'departure_date_time' => $this->getDepartureDateTime()->format('Y-m-d H:i:s'),
                'arrival_date_time' => $this->getArrivalDateTime()->format('Y-m-d H:i:s'),
                'trip_price' => $this->getTripPrice(),
                'seats_available' => $this->getSeatsAvailable(),
                'pet_allowed' => $this->isPetAllowed() ? 1 : 0,
                'smoking_allowed' => $this->isSmokingAllowed() ? 1 : 0,
                'id_car' => $this->getIdCar(),
                'id_user' => $this->getIdUser(),
                'status' => $this->getStatus()
            ];

            // verifier si le trajet est nouveau ou existant
            if ($this->id_trip === null) {
                $stmt = $db->prepare('INSERT INTO trips (trip_name, trip_description, departure_location, arrival_location,
               departure_date_time, arrival_date_time, trip_price, seats_available, pet_allowed, smoking_allowed,
               id_car, id_user, status) VALUES (:trip_name, :trip_description, :departure_location, :arrival_location,
               :departure_date_time, :arrival_date_time, :trip_price, :seats_available, :pet_allowed, :smoking_allowed,
               :id_car, :id_user, :status)'); // Correction : ':id_user' a été ajouté ici
                $stmt->execute($data);
                $this->id_trip = (int)$db->lastInsertId(); // Récupérer l'ID du dernier enregistrement inséré

            } else {
                $stmt = $db->prepare('UPDATE trips SET trip_name = :trip_name, trip_description = :trip_description,
               departure_location = :departure_location, arrival_location = :arrival_location,
               departure_date_time = :departure_date_time, arrival_date_time = :arrival_date_time,
               trip_price = :trip_price, seats_available = :seats_available, pet_allowed = :pet_allowed,
               smoking_allowed = :smoking_allowed, id_car = :id_car, id_user = :id_user, status = :status WHERE id_trip = :id_trip');

                $stmt->execute(['id_trip' => $this->id_trip] + $data);
            }
            return true;
        } catch (PDOException $e) {
            // Log the error message
            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la sauvegarde du trajet: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    // Méthode pour supprimer un trajet
    public function delete(): bool
    {
        if ($this->id_trip === null) {
            return false; // Impossible de supprimer un trajet non existant
        }
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM trips WHERE id_trip = :id_trip');
        $stmt->bindParam(':id_trip', $this->id_trip, PDO::PARAM_INT);
        try {
            return $stmt->execute();
        } catch (PDOException $e) {

            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Erreur lors de la suppression du trajet: ' . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }
}



