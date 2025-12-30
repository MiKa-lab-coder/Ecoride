<?php

namespace App\Models;

use App\Models\Database\Database;
use App\Models\Booking;
use App\Models\Rating;
use DateTime;
use PDO;
use PDOException;

class Trip extends BaseModel
{
    protected string $table = 'TRIPS';

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

    // Nouvelles propriétés pour les jointures
    private ?string $driver_firstname = null;
    private ?string $driver_name = null;
    private ?string $driver_email = null;
    private ?string $brand = null;
    private ?string $model = null;
    private ?string $registration_number = null;
    private ?string $driver_photo = null;
    private ?float $driver_rating = null;
    private ?string $energy_type = null;


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

    // Getters pour les nouvelles propriétés
    public function getDriverFirstname(): ?string
    {
        return $this->driver_firstname;
    }

    public function getDriverName(): ?string
    {
        return $this->driver_name;
    }

    public function getDriverEmail(): ?string
    {
        return $this->driver_email;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registration_number;
    }

    public function getDriverPhoto(): ?string
    {
        // Assure que le chemin de la photo est toujours absolu pour le front-end
        if ($this->driver_photo === null || $this->driver_photo === '') {
            return '/uploads/default.png'; // Chemin par défaut absolu
        }
        // Si le chemin est déjà absolu, le retourner tel quel
        if (strpos($this->driver_photo, '/') === 0) {
            return $this->driver_photo;
        }
        // Si le chemin est relatif (ex: 'uploads/image.png'), le rendre absolu
        return '/' . $this->driver_photo;
    }

    public function getDriverRating(): ?float
    {
        return $this->driver_rating;
    }

    public function getEnergyType(): ?string
    {
        return $this->energy_type;
    }


    // Setters
    public function setTripId(?int $trip_id): void
    {
        $this->trip_id = $trip_id;
    }

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

    // Setters pour les nouvelles propriétés
    public function setDriverFirstname(?string $driver_firstname): void
    {
        $this->driver_firstname = $driver_firstname;
    }

    public function setDriverName(?string $driver_name): void
    {
        $this->driver_name = $driver_name;
    }

    public function setDriverEmail(?string $driver_email): void
    {
        $this->driver_email = $driver_email;
    }

    public function setBrand(?string $brand): void
    {
        $this->brand = $brand;
    }

    public function setModel(?string $model): void
    {
        $this->model = $model;
    }

    public function setRegistrationNumber(?string $registration_number): void
    {
        $this->registration_number = $registration_number;
    }

    public function setDriverPhoto(?string $driver_photo): void
    {
        $this->driver_photo = $driver_photo;
    }

    public function setDriverRating(?float $driver_rating): void
    {
        $this->driver_rating = $driver_rating;
    }

    public function setEnergyType(?string $energy_type): void
    {
        $this->energy_type = $energy_type;
    }


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

    /**
     * Hydrate un objet Trip à partir d'un tableau de données.
     */
    public static function hydrate(array $data): Trip
    {
        $trip = new self(
            new DateTime($data['departure_day']),
            new DateTime($data['arrival_day']),
            $data['departure_location'],
            $data['arrival_location'],
            new DateTime($data['departure_time']),
            new DateTime($data['arrival_time']),
            (int)($data['trip_time'] ?? 0),
            (int)($data['trip_price'] ?? 0),
            $data['trip_nature'] ?? 'standard',
            (bool)($data['animal_pref'] ?? false),
            (bool)($data['smoking_pref'] ?? false),
            (int)($data['seating'] ?? 0),
            $data['status'] ?? 'pending',
            (int)($data['driver_id'] ?? 0),
            (int)($data['vehicle_id'] ?? 0)
        );
        $trip->setTripId(isset($data['trip_id']) ? (int)$data['trip_id'] : null);

        // Hydratation des nouvelles propriétés
        if (isset($data['driver_firstname'])) $trip->setDriverFirstname($data['driver_firstname']);
        if (isset($data['driver_name'])) $trip->setDriverName($data['driver_name']);
        if (isset($data['driver_email'])) $trip->setDriverEmail($data['driver_email']);
        if (isset($data['brand'])) $trip->setBrand($data['brand']);
        if (isset($data['model'])) $trip->setModel($data['model']);
        if (isset($data['registration_number'])) $trip->setRegistrationNumber($data['registration_number']);
        if (isset($data['driver_photo'])) $trip->setDriverPhoto($data['driver_photo']);
        if (isset($data['driver_rating'])) $trip->setDriverRating((float)$data['driver_rating']);
        if (isset($data['energy_type'])) $trip->setEnergyType($data['energy_type']);

        return $trip;
    }

    /**
     * Sauvegarde un objet Trip dans la base de données.
     */
    public function save(): bool
    {
        $db = Database::getInstance();
        try {
            if ($this->getTripId() !== null) {
                $stmt = $db->prepare("UPDATE TRIPS SET departure_day = :departure_day, arrival_day = :arrival_day,
                 departure_location = :departure_location, arrival_location = :arrival_location, departure_time = :departure_time,
                 arrival_time = :arrival_time, trip_time = :trip_time, trip_price = :trip_price,
                 trip_nature = :trip_nature, animal_pref = :animal_pref, smoking_pref = :smoking_pref, seating = :seating,
                 status = :status, driver_id = :driver_id, vehicle_id = :vehicle_id WHERE trip_id = :trip_id");
                $stmt->bindValue(':trip_id', $this->getTripId(), PDO::PARAM_INT);
            } else {
                $stmt = $db->prepare("INSERT INTO TRIPS (departure_day, arrival_day, departure_location, arrival_location,
                   departure_time, arrival_time, trip_time, trip_price, trip_nature, animal_pref, smoking_pref,
                   seating, status, driver_id, vehicle_id)
                VALUES (:departure_day, :arrival_day, :departure_location, :arrival_location,:departure_time, :arrival_time,
                        :trip_time, :trip_price, :trip_nature, :animal_pref, :smoking_pref, :seating, :status, :driver_id, :vehicle_id)");

            }

            $stmt->bindValue(':departure_day', $this->getDepartureDay()->format('Y-m-d'));
            $stmt->bindValue(':arrival_day', $this->getArrivalDay()->format('Y-m-d'));
            $stmt->bindValue(':departure_location', $this->getDepartureLocation());
            $stmt->bindValue(':arrival_location', $this->getArrivalLocation());
            $stmt->bindValue(':departure_time', $this->getDepartureTime()->format('H:i:s'));
            $stmt->bindValue(':arrival_time', $this->getArrivalTime()->format('H:i:s'));
            $stmt->bindValue(':trip_time', $this->getTripTime(), PDO::PARAM_INT);
            $stmt->bindValue(':trip_price', $this->getTripPrice(), PDO::PARAM_INT);
            $stmt->bindValue(':trip_nature', $this->getTripNature());
            $stmt->bindValue(':animal_pref', (int)$this->getAnimalPref(), PDO::PARAM_INT);
            $stmt->bindValue(':smoking_pref', (int)$this->getSmokingPref(), PDO::PARAM_INT);
            $stmt->bindValue(':seating', $this->getSeating(), PDO::PARAM_INT);
            $stmt->bindValue(':status', $this->getStatus());
            $stmt->bindValue(':driver_id', $this->getDriverId(), PDO::PARAM_INT);
            $stmt->bindValue(':vehicle_id', $this->getVehicleId(), PDO::PARAM_INT);

            $stmt->execute();

            if ($this->getTripId() === null) {
                $this->setTripId((int)$db->lastInsertId());
            }
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère un trajet par son ID.
     */
    public static function findById(int $trip_id): ?Trip
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT * FROM TRIPS WHERE trip_id = :trip_id");
            $stmt->bindValue(':trip_id', $trip_id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ? self::hydrate($data) : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Trouve un trajet par son statut.
     */
    public static function findByStatus(string $status): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                SELECT t.*, u.email as driver_email 
                FROM TRIPS t
                JOIN USERS u ON t.driver_id = u.user_id
                WHERE t.status = :status
            ");
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tripData) {
                $trips[] = self::hydrate($tripData);
            }
            return $trips;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Trouve les trajets par conducteur.
     */
    public static function getTripsByDriverId(int $driver_id): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                SELECT 
                    t.*,
                    u.firstname as driver_firstname,
                    u.name as driver_name,
                    v.brand,
                    v.model,
                    v.registration_number
                FROM TRIPS t
                JOIN USERS u ON t.driver_id = u.user_id
                JOIN VEHICLES v ON t.vehicle_id = v.vehicle_id
                WHERE t.driver_id = :driver_id AND (t.status = 'approved' OR t.status = 'ongoing')
                ORDER BY t.departure_day DESC, t.departure_time DESC
            ");
            $stmt->bindValue(':driver_id', $driver_id, PDO::PARAM_INT);
            $stmt->execute();

            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tripData) {
                $trips[] = self::hydrate($tripData);
            }
            return $trips;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Trouve les trajets "complétés" par utilisateur.
     */
    public static function getCompletedTripsByUser(int $userId): array
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("
                SELECT DISTINCT 
                    t.*, 
                    u.name as driver_name, 
                    u.firstname as driver_firstname
                FROM TRIPS t
                JOIN USERS u ON t.driver_id = u.user_id
                LEFT JOIN BOOKINGS b ON t.trip_id = b.trip_id
                WHERE t.status = 'completed' 
                AND (t.driver_id = :driverId OR b.user_id = :passengerId)
                ORDER BY t.departure_day DESC, t.departure_time DESC
            ");
            $stmt->bindValue(':driverId', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':passengerId', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $trips = [];

            if (!$results) {
                return [];
            }

            foreach ($results as $tripData) {
                $tripObject = self::hydrate($tripData);
                $tripArray = $tripObject->toArray();

                $isDriver = ($userId === $tripObject->getDriverId());

                if ($isDriver) {
                    $passengers = Booking::getPassengersForTrip($tripObject->getTripId());
                    if (empty($passengers)) {
                        // Cas où le conducteur n'a pas eu de passagers
                        $tripArray['user_to_rate_id'] = null;
                        $tripArray['user_to_rate_name'] = 'Aucun passager';
                        $tripArray['has_rated'] = true; // On considère qu'il n'y a rien à noter
                        $trips[] = $tripArray;
                    } else {
                        // Une entrée par passager pour permettre la notation individuelle
                        foreach ($passengers as $passenger) {
                            $passengerTripArray = $tripArray; // Copie du tableau de base
                            $passengerTripArray['user_to_rate_id'] = $passenger['user_id'];
                            $passengerTripArray['user_to_rate_name'] = $passenger['firstname'] . ' ' . $passenger['name'];
                            $passengerTripArray['has_rated'] = Rating::hasUserRatedTrip($userId, $tripObject->getTripId(), $passenger['user_id']);
                            $trips[] = $passengerTripArray;
                        }
                    }
                } else {
                    // Cas où l'utilisateur était passager : il note le conducteur
                    $tripArray['user_to_rate_id'] = $tripObject->getDriverId();
                    $tripArray['user_to_rate_name'] = $tripObject->getDriverFirstname() . ' ' . $tripObject->getDriverName();
                    $tripArray['has_rated'] = Rating::hasUserRatedTrip($userId, $tripObject->getTripId(), $tripObject->getDriverId());
                    $trips[] = $tripArray;
                }
            }
            return $trips;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Transforme un objet Trip en tableau de données.
     */
    public function toArray(): array
    {
        return [
            'trip_id' => $this->getTripId(),
            'departure_day' => $this->getDepartureDay()->format('Y-m-d'),
            'arrival_day' => $this->getArrivalDay()->format('Y-m-d'),
            'departure_location' => $this->getDepartureLocation(),
            'arrival_location' => $this->getArrivalLocation(),
            'departure_time' => $this->getDepartureTime()->format('H:i:s'),
            'arrival_time' => $this->getArrivalTime()->format('H:i:s'),
            'trip_time' => $this->getTripTime(),
            'trip_price' => $this->getTripPrice(),
            'trip_nature' => $this->getTripNature(),
            'animal_pref' => $this->getAnimalPref(),
            'smoking_pref' => (bool)$this->getSmokingPref(),
            'seating' => $this->getSeating(),
            'status' => $this->getStatus(),
            'driver_id' => $this->getDriverId(),
            'vehicle_id' => $this->getVehicleId(),
            'driver_firstname' => $this->getDriverFirstname(),
            'driver_name' => $this->getDriverName(),
            'driver_email' => $this->getDriverEmail(),
            'brand' => $this->getBrand(),
            'model' => $this->getModel(),
            'registration_number' => $this->getRegistrationNumber(),
            'driver_photo' => $this->getDriverPhoto(),
            'driver_rating' => $this->getDriverRating(),
            'energy_type' => $this->getEnergyType(),
        ];
    }

    /**
     * Supprime un trajet par son ID.
     */
    public function delete(): bool
    {
        if ($this->getTripId() === null) {
            return false;
        }
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("DELETE FROM TRIPS WHERE trip_id = :trip_id");
            $stmt->bindValue(':trip_id', $this->getTripId(), PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Calcul le nombre de places restantes pour un trajet.
     */
    public function calculateRemainingSeats(): int
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("SELECT COUNT(*) as total_booked FROM BOOKINGS WHERE trip_id = :trip_id");
            $stmt->bindValue(':trip_id', $this->getTripId(), PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_booked = $result ? (int)$result['total_booked'] : 0;
            return $this->getSeating() - $total_booked;
        } catch (PDOException $e) {
            return $this->getSeating();
        }
    }

    /**
     * Décrémente le nombre de places restantes pour un trajet.
     */
    public function decrementAvailableSeats(): bool
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("UPDATE TRIPS SET seating = seating - 1 WHERE trip_id = :trip_id AND seating > 0");
            $stmt->bindValue(':trip_id', $this->getTripId(), PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Incrémente le nombre de places restantes pour un trajet (en cas de réservation annulée)
     */
    public function incrementAvailableSeats(): bool
    {
        $db = Database::getInstance();
        try {
            $stmt = $db->prepare("UPDATE TRIPS SET seating = seating + 1 WHERE trip_id = :trip_id");
            $stmt->bindValue(':trip_id', $this->getTripId(), PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Récupère le nombre de trajets par jour sur les 7 derniers jours.
     */
    public static function getTripsCountLast7Days(): array
    {
        $db = Database::getInstance();
        try {
            // On récupère les trajets des 7 derniers jours
            $stmt = $db->prepare("
                SELECT departure_day, COUNT(*) as count
                FROM TRIPS
                WHERE departure_day >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY departure_day
                ORDER BY departure_day ASC
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // On prépare les données pour Chart.js (labels et data)
            // On s'assure d'avoir tous les jours, même ceux sans trajet (optionnel mais mieux)
            $data = [];
            $labels = [];
            
            // Création d'un tableau associatif date => count
            $countsByDate = [];
            foreach ($results as $row) {
                $countsByDate[$row['departure_day']] = (int)$row['count'];
            }

            // Boucle sur les 7 derniers jours
            for ($i = 6; $i >= 0; $i--) {
                $date = (new DateTime())->modify("-$i days")->format('Y-m-d');
                $labels[] = (new DateTime($date))->format('d/m'); // Format jour/mois
                $data[] = $countsByDate[$date] ?? 0;
            }

            return ['labels' => $labels, 'data' => $data];

        } catch (PDOException $e) {
            return ['labels' => [], 'data' => []];
        }
    }

    /**
     * Recherche des trajets avec filtres.
     */
    public static function searchTrips(array $filters): array
    {
        $db = Database::getInstance();
        try {
            $sql = "
                SELECT 
                    t.*,
                    u.firstname as driver_firstname,
                    u.name as driver_name,
                    u.photo as driver_photo,
                    (SELECT AVG(r.rating_value) FROM RATINGS r WHERE r.rated_user_id = u.user_id) as driver_rating,
                    v.brand,
                    v.model,
                    v.registration_number,
                    v.energy_type
                FROM TRIPS t
                JOIN USERS u ON t.driver_id = u.user_id
                JOIN VEHICLES v ON t.vehicle_id = v.vehicle_id
                WHERE t.status = 'approved'
                AND t.seating > 0
            ";

            $params = [];

            // Filtre par ID (pour les détails)
            if (!empty($filters['trip_id'])) {
                $sql .= " AND t.trip_id = :trip_id";
                $params[':trip_id'] = (int)$filters['trip_id'];
            }

            // Filtre par ville de départ
            if (!empty($filters['departure'])) {
                $sql .= " AND t.departure_location LIKE :departure";
                $params[':departure'] = '%' . $filters['departure'] . '%';
            }

            // Filtre par ville d'arrivée
            if (!empty($filters['arrival'])) {
                $sql .= " AND t.arrival_location LIKE :arrival";
                $params[':arrival'] = '%' . $filters['arrival'] . '%';
            }

            // Filtre par date
            if (!empty($filters['departure_day'])) {
                $sql .= " AND t.departure_day = :departure_day";
                $params[':departure_day'] = $filters['departure_day'];
            }

            // Filtre écologique (voiture électrique ou hybride)
            if (!empty($filters['ecologic']) && $filters['ecologic'] === 'true') {
                $sql .= " AND t.trip_nature = 'ecologic'";
            }

            // Filtre prix max
            if (!empty($filters['max_price'])) {
                $sql .= " AND t.trip_price <= :max_price";
                $params[':max_price'] = (int)$filters['max_price'];
            }

            // Filtre durée max (en minutes)
            if (!empty($filters['max_duration'])) {
                $sql .= " AND t.trip_time <= :max_duration";
                $params[':max_duration'] = (int)$filters['max_duration'];
            }

            // Filtre note minimale du conducteur
            if (!empty($filters['min_rating'])) {
                $sql .= " AND (SELECT AVG(r.rating_value) FROM RATINGS r WHERE r.rated_user_id = u.user_id) >= :min_rating";
                $params[':min_rating'] = (float)$filters['min_rating'];
            }

            $sql .= " ORDER BY t.departure_day ASC, t.departure_time ASC";

            $stmt = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            $trips = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tripData) {
                $trips[] = self::hydrate($tripData);
            }
            return $trips;

        } catch (PDOException $e) {
            return [];
        }
    }
}