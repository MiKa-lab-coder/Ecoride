<?php

namespace App\Models;

use App\Models\Database\Database;
use DateTime;
use PDO;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Vehicle extends BaseModel
{
    /**
     * @var string le nom de table associé au modèle
     */
    protected string $table = 'VEHICLES';

    // Attributs

    private ?int $vehicle_id = null;
    private DateTime $first_service;
    private string $registration_number;// format: AB-123-XY
    private string $energy_type;
    private string $brand;
    private string $model;
    private ?string $color;
    private int $seating_capacity;
    private int $user_id;


    // Getters et Setters
    public function getVehicleId(): ?int
    {
        return $this->vehicle_id;
    }

    public function setVehicleId(int $vehicle_id): void
    {
        $this->vehicle_id = $vehicle_id;
    }

    public function getFirstService(): DateTime
    {
        return $this->first_service;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registration_number;
    }


    public function getEnergyType(): string
    {
        return $this->energy_type;
    }


    public function getBrand(): string
    {
        return $this->brand;
    }


    public function getModel(): string
    {
        return $this->model;
    }


    public function getColor(): ?string
    {
        return $this->color;
    }


    public function getSeatingCapacity(): int
    {
        return $this->seating_capacity;
    }


    public function getUserId(): int
    {
        return $this->user_id;
    }

    // Setters pour les propriétés
    public function setFirstService(DateTime $first_service): void
    {
        $this->first_service = $first_service;
    }

    public function setRegistrationNumber(string $registration_number): void
    {
        $this->registration_number = $registration_number;
    }

    public function setEnergyType(string $energy_type): void
    {
        $this->energy_type = $energy_type;
    }

    public function setBrand(string $brand): void
    {
        $this->brand = $brand;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }

    public function setSeatingCapacity(int $seating_capacity): void
    {
        $this->seating_capacity = $seating_capacity;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }


    // Constructeur
    public function __construct(string $brand, string $model, string $registration_number, int $seating_capacity,
                                ?string $color, string $energy_type, DateTime $first_service, int $user_id,
                                ?int $vehicle_id = null)
    {
        parent::__construct();
        $this->brand = $brand;
        $this->model = $model;
        $this->registration_number = $registration_number;
        $this->seating_capacity = $seating_capacity;
        $this->color = $color;
        $this->energy_type = $energy_type;
        $this->first_service = $first_service;
        $this->user_id = $user_id;
        $this->vehicle_id = $vehicle_id;
    }

    // Méthodes

    public static function hydrate(array $data): Vehicle
    {
        $vehicle = new Vehicle(
            $data['brand'],
            $data['model'],
            $data['registration_number'],
            $data['seating_capacity'],
            $data['color'],
            $data['energy_type'],
            new DateTime($data['first_service']),
            $data['user_id'],
            $data['vehicle_id']
        );
        return $vehicle;
    }

    // Méthode pour enregistrer une nouvelle voiture dans la base de données
    public function save(): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        try {
            if ($this->vehicle_id) {
                // Mise à jour
                $sql = "UPDATE VEHICLES SET first_service = :first_service, registration_number = :registration_number,
                    energy_type = :energy_type, brand = :brand, model = :model, color = :color,
                    seating_capacity = :seating_capacity, user_id = :user_id WHERE vehicle_id = :vehicle_id";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':vehicle_id', $this->vehicle_id, PDO::PARAM_INT);
            } else {
                // Insertion
                $sql = "INSERT INTO VEHICLES (first_service, registration_number, energy_type, brand, model, color,
                      seating_capacity, user_id) 
                                  VALUES (:first_service, :registration_number, :energy_type, :brand, :model, :color,
                                          :seating_capacity, :user_id)";
                $stmt = $db->prepare($sql);
            }

            $stmt->bindValue(':first_service', $this->first_service->format('Y-m-d'));
            $stmt->bindValue(':registration_number', $this->registration_number);
            $stmt->bindValue(':energy_type', $this->energy_type);
            $stmt->bindValue(':brand', $this->brand);
            $stmt->bindValue(':model', $this->model);
            $stmt->bindValue(':color', $this->color);
            $stmt->bindValue(':seating_capacity', $this->seating_capacity, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $this->user_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                if (!$this->vehicle_id) {
                    $this->vehicle_id = (int)$db->lastInsertId();
                }
                return true;
            } else {
                $logger->error('Échec de l\'enregistrement du véhicule dans la base de données.');
                return false;
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO', ['exception' => $e]);
            return false;
        }
    }

    // Méthode pour supprimer un véhicule
    public static function delete(int $vehicle_id): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        try {
            $stmt = $db->prepare("DELETE FROM VEHICLES WHERE vehicle_id = :vehicle_id");
            $stmt->bindValue(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            $logger->error('Erreur PDO', ['exception' => $e]);
            return false;
        }
    }

    // Méthode pour recuperer les véhicules par utilisateur
    public static function getVehiclesByUserId(int $user_id): array
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        $vehicles = [];
        try {
            $stmt = $db->prepare("SELECT * FROM VEHICLES WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $data) {
                $vehicles[] = self::hydrate($data);
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO', ['exception' => $e]);
            return [];
        }
        return $vehicles;
    }

    // trouver un véhicule par son ID
    public static function findById(int $vehicle_id): ?Vehicle
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        try {
            $stmt = $db->prepare("SELECT * FROM VEHICLES WHERE vehicle_id = :vehicle_id");
            $stmt->bindValue(':vehicle_id', $vehicle_id, PDO::PARAM_INT);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            } else {
                return null;
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO', ['exception' => $e]);
            return null;
        }
    }

    // trouver un véhicule par son numéro d'immatriculation
    public static function findByRegistration(string $registration_number): ?Vehicle
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        try {
            $stmt = $db->prepare("SELECT * FROM VEHICLES WHERE registration_number = :registration_number");
            $stmt->bindValue(':registration_number', $registration_number);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            } else {
                return null;
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO', ['exception' => $e]);
            return null;
        }
    }

    // Methode pour trouver un véhicule par son énergie pour possibilité de filtre (combustion, électrique, hybride)
    public static function findByEnergy(string $energy_type): array
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        $vehicles = [];
        try {
            $stmt = $db->prepare("SELECT * FROM VEHICLES WHERE energy_type = :energy_type");
            $stmt->bindValue(':energy_type', $energy_type);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $data) {
                $vehicles[] = self::hydrate($data);
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO', ['exception' => $e]);
            return [];
        }
        return $vehicles;
    }
}