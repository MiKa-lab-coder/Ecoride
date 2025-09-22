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
    /**
     * @var string Le nom de la table associée au modèle
     */
    protected string $table = 'TRIPS';

    // Propriétés
    private ?int $trip_id = null;
    private DateTime $departure_day;
    private DateTime $arrival_day;
    private string $departure_location;
    private string $arrival_location;
    private DateTime $departure_time;
    private DateTime $arrival_time;
    private int $trip_time;
    private int $trip_price;
    private string $trip_nature;
    private bool $animal_pref;
    private bool $smoking_pref;
    private int $seating;
    private string $status;
    private int $driver_id;
    private int $vehicle_id;

    // Getters

    public function getTripId(): ?int
    {
        return $this->trip_id;
    }

    public function getDepartureDay(): DateTime
    {
        return $this->departure_day;
    }

    public function getArrivalDay(): DateTime
    {
        return $this->arrival_day;
    }

    public function getDepartureLocation(): string
    {
        return $this->departure_location;
    }

    public function getArrivalLocation(): string
    {
        return $this->arrival_location;
    }

    public function getDepartureTime(): DateTime
    {
        return $this->departure_time;
    }

    public function getArrivalTime(): DateTime
    {
        return $this->arrival_time;
    }

    public function getTripTime(): int
    {
        return $this->trip_time;
    }

    public function getTripPrice(): int
    {
        return $this->trip_price;
    }

    public function getTripNature(): string
    {
        return $this->trip_nature;
    }

    public function getAnimalPref(): bool
    {
        return $this->animal_pref;
    }

    public function getSmokingPref(): bool
    {
        return $this->smoking_pref;
    }

    public function getSeating(): int
    {
        return $this->seating;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDriverId(): int
    {
        return $this->driver_id;
    }

    public function getVehicleId(): int
    {
        return $this->vehicle_id;
    }

    // Setters
    public function setDepartureDay(DateTime $departure_day): void
    {
        $this->departure_day = $departure_day;
    }

    public function setArrivalDay(DateTime $arrival_day): void
    {
        $this->arrival_day = $arrival_day;
    }

    public function setDepartureLocation(string $departure_location): void
    {
        $this->departure_location = $departure_location;
    }

    public function setArrivalLocation(string $arrival_location): void
    {
        $this->arrival_location = $arrival_location;
    }

    public function setDepartureTime(DateTime $departure_time): void
    {
        $this->departure_time = $departure_time;
    }

    public function setArrivalTime(DateTime $arrival_time): void
    {
        $this->arrival_time = $arrival_time;
    }

    public function setTripTime(int $trip_time): void
    {
        $this->trip_time = $trip_time;
    }

    public function setTripPrice(int $trip_price): void
    {
        $this->trip_price = $trip_price;
    }

    public function setTripNature(string $trip_nature): void
    {
        $this->trip_nature = $trip_nature;
    }

    public function setAnimalPref(bool $animal_pref): void
    {
        $this->animal_pref = $animal_pref;
    }

    public function setSmokingPref(bool $smoking_pref): void
    {
        $this->smoking_pref = $smoking_pref;
    }

    public function setSeating(int $seating): void
    {
        $this->seating = $seating;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setDriverId(int $driver_id): void
    {
        $this->driver_id = $driver_id;
    }

    public function setVehicleId(int $vehicle_id): void
    {
        $this->vehicle_id = $vehicle_id;
    }

    // Constructeur
    public function __construct(DateTime $departure_day, DateTime $arrival_day, string $departure_location, string $arrival_location,
                                DateTime $departure_time, DateTime $arrival_time, int $trip_time, int $trip_price, string $trip_nature,
                                bool     $animal_pref, bool $smoking_pref, int $seating, string $status, int $driver_id,
                                int      $vehicle_id)
    {
        parent::__construct();
        $this->setDepartureDay($departure_day);
        $this->setArrivalDay($arrival_day);
        $this->setDepartureLocation($departure_location);
        $this->setArrivalLocation($arrival_location);
        $this->setDepartureTime($departure_time);
        $this->setArrivalTime($arrival_time);
        $this->setTripTime($trip_time);
        $this->setTripPrice($trip_price);
        $this->setTripNature($trip_nature);
        $this->setAnimalPref($animal_pref);
        $this->setSmokingPref($smoking_pref);
        $this->setSeating($seating);
        $this->setStatus($status);
        $this->setDriverId($driver_id);
        $this->setVehicleId($vehicle_id);
    }

    // Méthode pour hydrater l'objet Trip
    public static function hydrate(array $data): Trip
    {
        $trip = new self(
            new DateTime($data['departure_day']),
            new DateTime($data['arrival_day']),
            $data['departure_location'],
            $data['arrival_location'],
            new DateTime($data['departure_time']),
            new DateTime($data['arrival_time']),
            (int)$data['trip_time'],
            (int)$data['trip_price'],
            $data['trip_nature'],
            (bool)$data['animal_pref'],
            (bool)$data['smoking_pref'],
            (int)$data['seating'],
            $data['status'],
            (int)$data['driver_id'],
            (int)$data['vehicle_id']
        );
        // Assigner l'ID du trajet s'il est présent dans les données
        $trip->trip_id = isset($data['trip_id']) ? (int)$data['trip_id'] : null;

        return $trip;
    }

    public static function searchTrips(mixed $departure, mixed $arrival, mixed $departureDay, array $filters): array
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_searchTrips');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));

        try {
            $sql = "SELECT * FROM TRIPS WHERE departure_location LIKE :departure AND arrival_location LIKE :arrival AND
                          departure_day = :departure_day AND status = :status";
            $params = [
                ':departure' => '%' . $departure . '%',
                ':arrival' => '%' . $arrival . '%',
                ':departure_day' => $departureDay,
                ':status' => 'approved' // Seuls les trajets approuvés sont affichés
            ];

            // Ajout des filtres dynamiquement
            if (isset($filters['max_price'])) {
                $sql .= " AND trip_price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }
            if (isset($filters['trip_nature'])) {
                $sql .= " AND trip_nature = :trip_nature";
                $params[':trip_nature'] = $filters['trip_nature'];
            }
            if (isset($filters['animal_pref'])) {
                $sql .= " AND animal_pref = :animal_pref";
                $params[':animal_pref'] = (int)$filters['animal_pref'];
            }
            if (isset($filters['smoking_pref'])) {
                $sql .= " AND smoking_pref = :smoking_pref";
                $params[':smoking_pref'] = (int)$filters['smoking_pref'];
            }
            if (isset($filters['seating'])) {
                $sql .= " AND seating >= :seating";
                $params[':seating'] = $filters['seating'];
            }

            $stmt = $db->prepare($sql);
            foreach ($params as $key => &$val) {
                if (is_int($val)) {
                    $stmt->bindParam($key, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindParam($key, $val, PDO::PARAM_STR);
                }
            }
            $stmt->execute();

            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $trip) {
                $trips[] = self::hydrate($trip);
            }
            return $trips;

        } catch (PDOException $e) {
            $logger->error("Erreur lors de la recherche des trajets : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    // Méthode pour créer un nouveau trajet ou mettre à jour un trajet existant
    public function save(): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_save');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));

        try {
            if ($this->trip_id !== null) {
                // UPDATE
                $stmt = $db->prepare("UPDATE TRIPS SET departure_day = :departure_day, arrival_day = :arrival_day,
                        departure_location = :departure_location,
                        arrival_location = :arrival_location, departure_time = :departure_time, arrival_time = :arrival_time,
                        trip_time = :trip_time,
                        trip_price = :trip_price, trip_nature = :trip_nature, animal_pref = :animal_pref,
                        smoking_pref = :smoking_pref,
                        seating = :seating, status = :status, driver_id = :driver_id, vehicle_id = :vehicle_id WHERE trip_id = :trip_id");
                $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            } else {
                // INSERT
                $stmt = $db->prepare("INSERT INTO TRIPS (departure_day, arrival_day, departure_location, arrival_location,
                        departure_time,
                        arrival_time, trip_time, trip_price, trip_nature, animal_pref, smoking_pref, seating, status, driver_id,
                        vehicle_id)
                        VALUES (:departure_day, :arrival_day, :departure_location, :arrival_location, :departure_time, :arrival_time,
                        :trip_time, :trip_price, :trip_nature, :animal_pref, :smoking_pref, :seating, :status, :driver_id, :vehicle_id)");
            }

            $departureDay = $this->departure_day->format('Y-m-d');
            $arrivalDay = $this->arrival_day->format('Y-m-d');
            $departureTime = $this->departure_time->format('H:i:s');
            $arrivalTime = $this->arrival_time->format('H:i:s');

            $stmt->bindParam(':departure_day', $departureDay, PDO::PARAM_STR);
            $stmt->bindParam(':arrival_day', $arrivalDay, PDO::PARAM_STR);
            $stmt->bindParam(':departure_location', $this->departure_location, PDO::PARAM_STR);
            $stmt->bindParam(':arrival_location', $this->arrival_location, PDO::PARAM_STR);
            $stmt->bindParam(':departure_time', $departureTime, PDO::PARAM_STR);
            $stmt->bindParam(':arrival_time', $arrivalTime, PDO::PARAM_STR);
            $stmt->bindParam(':trip_time', $this->trip_time, PDO::PARAM_INT);
            $stmt->bindParam(':trip_price', $this->trip_price, PDO::PARAM_INT);
            $stmt->bindParam(':trip_nature', $this->trip_nature, PDO::PARAM_STR);
            $stmt->bindParam(':animal_pref', $this->animal_pref, PDO::PARAM_INT);
            $stmt->bindParam(':smoking_pref', $this->smoking_pref, PDO::PARAM_INT);
            $stmt->bindParam(':seating', $this->seating, PDO::PARAM_INT);
            $stmt->bindParam(':status', $this->status, PDO::PARAM_STR);
            $stmt->bindParam(':driver_id', $this->driver_id, PDO::PARAM_INT);
            $stmt->bindParam(':vehicle_id', $this->vehicle_id, PDO::PARAM_INT);

            $stmt->execute();

            if ($this->trip_id === null) {
                $this->trip_id = (int)$db->lastInsertId();
            }

            return true;

        } catch (PDOException $e) {
            $logger->error("Erreur lors de la sauvegarde du trajet : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    // Méthode pour supprimer un trajet
    public function delete(): bool
    {
        if ($this->trip_id === null) {
            return false;
        }
        $db = Database::getInstance();
        $logger = new Logger('trip_delete');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("DELETE FROM TRIPS WHERE trip_id = :trip_id");
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la suppression du trajet : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    // Méthode pour trouver un trajet par son ID
    public static function findById(int $trip_id): ?Trip
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_findById');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("SELECT * FROM TRIPS WHERE trip_id = :trip_id");
            $stmt->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            }
            return null;
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la recherche du trajet par ID : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

// Méthode pour récupérer/afficher tous les trajets d'un utilisateur (conducteur ou passager)
    public static function findByDriverOrUserId(int $user_id): array
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_findByUserId');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("SELECT * FROM TRIPS WHERE driver_id = :user_id
                                  OR trip_id IN (SELECT trip_id FROM BOOKINGS WHERE user_id = :user_id)");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $trip) {
                $trips[] = self::hydrate($trip);
            }
            return $trips;
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la recherche des trajets par ID utilisateur : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    // Méthode calculer le nombre de places restantes pour un trajet
    public function calculateRemainingSeats(): int
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_calculateAvailableSeats');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("SELECT SUM(seats_booked) AS total_booked FROM BOOKINGS WHERE trip_id = :trip_id");
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_booked = $result['total_booked'] ?? 0;
            return $this->seating - (int)$total_booked;
        } catch (PDOException $e) {
            $logger->error("Erreur lors du calcul des places disponibles : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return $this->seating; // En cas d'erreur, retourner le nombre total de places
        }
    }

    // Méthode pour convertir l'objet Trip en tableau associatif
    public function toArray(): array
    {
        return [
            'trip_id' => $this->trip_id,
            'departure_day' => $this->departure_day->format('Y-m-d'),
            'arrival_day' => $this->arrival_day->format('Y-m-d'),
            'departure_location' => $this->departure_location,
            'arrival_location' => $this->arrival_location,
            'departure_time' => $this->departure_time->format('H:i:s'),
            'arrival_time' => $this->arrival_time->format('H:i:s'),
            'trip_time' => $this->trip_time,
            'trip_price' => $this->trip_price,
            'trip_nature' => $this->trip_nature,
            'animal_pref' => $this->animal_pref,
            'smoking_pref' => $this->smoking_pref,
            'seating' => $this->seating,
            'status' => $this->status,
            'driver_id' => $this->driver_id,
            'vehicle_id' => $this->vehicle_id
        ];
    }

    // Méthode pour récupérer les passagers d'un trajet
    public function getPassengers(): array
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_getPassengers');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("SELECT U.* FROM USERS U
                                  JOIN BOOKINGS B ON U.user_id = B.user_id
                                  WHERE B.trip_id = :trip_id");
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la récupération des passagers : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    // Méthode pour trouver un trajet par son status
    public static function findByStatus(string $status): array
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_findByStatus');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("SELECT * FROM TRIPS WHERE status = :status");
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $trip) {
                $trips[] = self::hydrate($trip);
            }
            return $trips;
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la recherche des trajets par status : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    // Méthode pour décompter le nombre de places disponibles après une réservation
    public function decrementAvailableSeats(): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_decrementAvailableSeats');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            // S'assurer que le nombre de places ne devient pas négatif
            $stmt = $db->prepare("UPDATE TRIPS SET seating = seating - 1 WHERE trip_id = :trip_id AND seating > 0");
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $logger->error("Erreur lors du décompte des places disponibles : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    // Méthode pour incrementer le nombre de places disponibles après une annulation de réservation
    public function incrementAvailableSeats(): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_incrementAvailableSeats');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            // S'assurer que le nombre de places ne dépasse pas la capacité de places indiquée par le conducteur pour ce trajet
            $stmt = $db->prepare("UPDATE TRIPS SET seating = seating + 1 WHERE trip_id = :trip_id AND seating < total_seats");
            $stmt->bindParam(':trip_id', $this->trip_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            $logger->error("Erreur lors de l'incrémentation des places disponibles : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    // Méthode pour recupérer tous les trajets proposés par un conducteur
    public static function getTripsByDriverId(int $driver_id): array
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_getTripsByDriverId');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("SELECT * FROM trips WHERE driver_id = ? ORDER BY departure_day DESC, departure_time DESC");
            $stmt->execute([$driver_id]);
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $trip) {
                $trips[] = self::hydrate($trip);
            }
            return $trips;
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la récupération des trajets par ID conducteur : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    // Méthode pour récupérer tous les trajets complétés par un conducteur ou un passager
    public static function getCompletedTripsByUser($userId): array
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_getCompletedTripsByUser');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));

        try {
            $stmt = $db->prepare("
            SELECT DISTINCT t.*
            FROM TRIPS t
            LEFT JOIN BOOKINGS b ON t.trip_id = b.trip_id
            WHERE t.status = 'completed' AND (t.driver_id = :userId OR b.user_id = :userId)
            ORDER BY t.departure_day DESC, t.departure_time DESC
        ");

            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $trip) {
                $trips[] = self::hydrate($trip);
            }

            return $trips;
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la récupération des trajets terminés de l'utilisateur : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    // Méthode pour récupérer tous les trajets fait par jour pendant les 7 derniers jours
    public static function getTripsCountLast7Days(): array
    {
        $db = Database::getInstance();
        $logger = new Logger('trip_getTripsCountLast7Days');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
        try {
            $stmt = $db->prepare("
                SELECT departure_day, COUNT(*) AS trip_count
                FROM TRIPS
                WHERE departure_day >= CURDATE() - INTERVAL 7 DAY
                GROUP BY departure_day
                ORDER BY departure_day DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $logger->error("Erreur lors de la récupération du nombre de trajets des 7 derniers jours : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }
}