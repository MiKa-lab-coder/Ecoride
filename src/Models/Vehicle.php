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
    protected string $table = 'vehicles';

    // Attributs

    private ?int $id_car = null;
    private DateTime $date_first_service;
    private string $registration_number;
    private string $energy;
    private string $brand;
    private string $model;
    private string $color;
    private int $seat_capacity;
    private int $owner_id;


    // Getters et Setters
    public function getIdCar(): ?int
    {
        return $this->id_car;
    }

    public function setIdCar(int $id_car): void
    {
        $this->id_car = $id_car;
    }

    public function getDateFirstService(): DateTime
    {
        return $this->date_first_service;
    }


    public function getRegistrationNumber(): string
    {
        return $this->registration_number;
    }


    public function getEnergy(): string
    {
        return $this->energy;
    }


    public function getBrand(): string
    {
        return $this->brand;
    }


    public function getModel(): string
    {
        return $this->model;
    }


    public function getColor(): string
    {
        return $this->color;
    }


    public function getSeatCapacity(): int
    {
        return $this->seat_capacity;
    }


    public function getOwnerId(): int
    {
        return $this->owner_id;
    }


    // Constructeur
    public function __construct(string $brand, string $model, string $registration_number, int $seat_capacity,
                                string $color, string $energy, DateTime $date_first_service, int $owner_id, ?int $id_car = null)
    {
        parent::__construct();
        /**
         * Initialisation des attributs
         * On n'utilise pas les setters dans le constructeur, car on ne modifie pas un objet voiture
         * Soit on le crée, soit on le détruit (modification interdite en France pour les véhicules)
         */
        $this->brand = $brand;
        $this->model = $model;
        $this->registration_number = $registration_number;
        $this->seat_capacity = $seat_capacity;
        $this->color = $color;
        $this->energy = $energy;
        $this->date_first_service = $date_first_service;
        $this->owner_id = $owner_id;
        $this->id_car = $id_car;

    }

    // Méthodes

    public static function hydrate(array $data): Vehicle
    {
        $vehicle = new Vehicle(
            $data['brand'],
            $data['model'],
            $data['registration_number'],
            $data['seat_capacity'],
            $data['color'],
            $data['energy'],
            new DateTime($data['date_first_service']),
            $data['owner_id'],
            $data['id_car']
        );
        return $vehicle;
    }

    // Méthode pour enregistrer une nouvelle voiture dans la base de données
    public static function save(Vehicle $vehicle): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        try {
            $stmt = $db->prepare("INSERT INTO vehicles (date_first_service, registration_number, energy, brand, model, color, seat_capacity, owner_id) 
                                  VALUES (:date_first_service, :registration_number, :energy, :brand, :model, :color, :seat_capacity, :owner_id)");
            $stmt->bindValue(':date_first_service', $vehicle->getDateFirstService()->format('Y-m-d'));
            $stmt->bindValue(':registration_number', $vehicle->getRegistrationNumber());
            $stmt->bindValue(':energy', $vehicle->getEnergy());
            $stmt->bindValue(':brand', $vehicle->getBrand());
            $stmt->bindValue(':model', $vehicle->getModel());
            $stmt->bindValue(':color', $vehicle->getColor());
            $stmt->bindValue(':seat_capacity', $vehicle->getSeatCapacity(), PDO::PARAM_INT);
            $stmt->bindValue(':owner_id', $vehicle->getOwnerId(), PDO::PARAM_INT);

            if ($stmt->execute()) {
                // Récupérer l'ID et le définir dans l'objet
                $vehicle->setIdCar((int)$db->lastInsertId());
                return true;
            } else {
                $logger->error('Échec de l\'insertion de la voiture dans la base de données.');
                return false;
            }
        } catch (PDOException $e) {
            // Gérer les erreurs de connexion ou d'exécution
            $logger->error('Erreur PDO', ['exception' => $e]);
            return false;
        }
    }

    // Méthode pour supprimer ma voiture (sélection par plaque d'immatriculation)
    public static function delete(string $registration_number): bool
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        try {
            $stmt = $db->prepare("DELETE FROM vehicles WHERE registration_number = :registration_number");
            $stmt->bindValue(':registration_number', $registration_number);
            return $stmt->execute();
        } catch (PDOException $e) {
            // Gérer les erreurs de connexion ou d'exécution
            $logger->error('Erreur PDO', ['exception' => $e]);
            return false;
        }
    }

    // Méthode pour recuperer les voitures (par utilisateur)
    public static function getVehiclesByOwnerId(int $owner_id): array
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        $vehicles = [];
        try {
            $stmt = $db->prepare("SELECT * FROM vehicles WHERE owner_id = :owner_id");
            $stmt->bindValue(':owner_id', $owner_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($results as $data) {
                $vehicles[] = self::hydrate($data);
            }
        } catch (PDOException $e) {
            // Gérer les erreurs de connexion ou d'exécution
            $logger->error('Erreur PDO', ['exception' => $e]);
            return [];
        }
        return $vehicles;
    }
    // trouver un véhicule par son numéro d'immatriculation
    public static function findByRegistration(string $registration_number): ?Vehicle
    {
        $db = Database::getInstance();
        $logger = new Logger('vehicle_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
        try {
            $stmt = $db->prepare("SELECT * FROM vehicles WHERE registration_number = :registration_number");
            $stmt->bindValue(':registration_number', $registration_number);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            } else {
                return null;
            }
        } catch (PDOException $e) {
            // Gérer les erreurs de connexion ou d'exécution
            $logger->error('Erreur PDO', ['exception' => $e]);
            return null;
        }
    }

}

