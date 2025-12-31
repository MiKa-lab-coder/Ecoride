<?php

namespace App\Controllers\VehicleController;

use App\Models\Vehicle;
use DateTime;
use App\Models\User;
use App\Services\Validator;
use App\Services\TokenValidator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

/**
 * Class VehicleController
 * Gère les actions liées aux véhicules.
 * On peut ajouter, modifier, supprimer et afficher les véhicules.
 * Pour la sécurité, on gère le XSS dans le controller (htmlspecialchars), le JWT avec TokenValidator,
 * et la validation de format des données avec Validator
 */
class VehicleController
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('vehicle_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/vehicle.log', Logger::DEBUG));
    }

    /**
     * Méthode pour ajouter/créer un véhicule
     */
    public function addCar(): void
    {
        header("Content-Type: application/json");
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Récupération des données l'utilisateur pour récupérer l'user_id nécessaire pour relier le véhicule à son propriétaire
            $user_id = $decodedToken->data->id;
            $user = User::find($user_id);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Vérification du nombre de véhicules
            $userVehicles = Vehicle::getVehiclesByUserId($user_id);
            if (count($userVehicles) >= 3) {
                throw new Exception("Vous ne pouvez pas enregistrer plus de 3 véhicules.", 403);
            }

            // Récupération et validation des données envoyées en JSON
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }

            // Vérification des champs obligatoires
            if (empty($data['registration_number'])
                || empty($data['first_service'])
                || empty($data['brand'])
                || empty($data['model'])
                || empty($data['color'])
                || empty($data['seating_capacity'])
                || empty($data['energy_type'])) {
                throw new Exception("Tous les champs obligatoires doivent être remplis.", 400);
            }

            // Validation des données
            $validator = new Validator();
            if (!$validator->validateRegistrationNumber($data['registration_number'])) {
                throw new Exception("Numéro d'immatriculation invalide.", 400);
            }
            // Utilisation de validateYmdDateFormat car le front envoie Y-m-d
            if (!$validator->validateYmdDateFormat($data['first_service'])) {
                throw new Exception("Date de première mise en circulation invalide. Format attendu: Y-m-d.", 400);
            }
            if (!$validator->validateBrand($data['brand'])) {
                throw new Exception("Marque invalide.", 400);
            }
            if (!$validator->validateModel($data['model'])) {
                throw new Exception("Modèle invalide.", 400);
            }
            if (!$validator->validateColor($data['color'])) {
                throw new Exception("Couleur invalide.", 400);
            }
            if (!$validator->validateSeatCapacity($data['seating_capacity'])) {
                throw new Exception("Capacité d'accueil invalide. Doit être un nombre entier entre 1 et 9.", 400);
            }
            if (!$validator->validateEnergyType($data['energy_type'])) {
                throw new Exception("Type d'énergie invalide.", 400);
            }

            // Normalisation de la plaque d'immatriculation
            $cleanPlate = strtoupper(str_replace('-', '', $data['registration_number']));
            $normalizedPlate = substr($cleanPlate, 0, 2) . '-' . substr($cleanPlate, 2, 3) . '-' . substr($cleanPlate, 5, 2);
            
            // Protection contre les attaques XSS
            $registration_number = htmlspecialchars($normalizedPlate, ENT_QUOTES, 'UTF-8');
            $first_service_str = htmlspecialchars($data['first_service'], ENT_QUOTES, 'UTF-8');
            $brand = htmlspecialchars($data['brand'], ENT_QUOTES, 'UTF-8');
            $model = htmlspecialchars($data['model'], ENT_QUOTES, 'UTF-8');
            $color = htmlspecialchars($data['color'], ENT_QUOTES, 'UTF-8');
            $seating_capacity = (int)$data['seating_capacity'];
            $energy_type = htmlspecialchars($data['energy_type'], ENT_QUOTES, 'UTF-8');
            $owner_id = $user->getUserId();

            // vérification que la date de première mise en circulation n'est pas dans le futur
            $date_first_service = new DateTime($first_service_str);
            $now = new DateTime();
            if ($date_first_service > $now) {
                throw new Exception("La date de première mise en circulation ne peut pas être dans le futur.", 400);
            }

            // Vérification que le numéro d'immatriculation est unique (donc que le véhicule n'existe pas déjà)
            $existingVehicle = Vehicle::findByRegistration($registration_number);
            if ($existingVehicle) {
                throw new Exception("Un véhicule avec ce numéro d'immatriculation existe déjà.", 400);
            }

            // Création du véhicule
            $vehicle = new Vehicle(
                $brand,
                $model,
                $registration_number,
                $seating_capacity,
                $color,
                $energy_type,
                $date_first_service,
                $owner_id
            );
            // Enregistrement du véhicule en base de données
            if ($vehicle->save()) {
                http_response_code(201);
                echo json_encode(["message" => "Véhicule ajouté avec succès.", "vehicle_id" => $vehicle->getVehicleId()]);
                $this->logger->info("Véhicule ajouté avec succès: " . $registration_number .
                    " par l'utilisateur ID: " . $owner_id);
                exit;
            } else {
                throw new Exception("Erreur lors de l'ajout du véhicule.", 500);
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'ajout du véhicule: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Méthode pour modifier un véhicule
     */
    public function deleteCar(int $vehicleId): void
    {
        header("Content-Type: application/json");
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Récupération des données l'utilisateur pour récupérer l'user_id nécessaire pour relier le véhicule à son propriétaire
            $user_id = $decodedToken->data->id;
            $user = User::find($user_id);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            
            // Vérification que le véhicule existe
            $vehicle = Vehicle::findById($vehicleId);
            if (!$vehicle) {
                throw new Exception("Véhicule non trouvé.", 404);
            }
            // Vérification que l'utilisateur est bien le propriétaire du véhicule
            if ($vehicle->getUserId() !== $user->getUserId()) {
                throw new Exception("Vous n'êtes pas autorisé à supprimer ce véhicule.", 403);
            }
            // Suppression du véhicule
            if (Vehicle::delete($vehicle->getVehicleId())) {
                http_response_code(200);
                echo json_encode(["message" => "Véhicule supprimé avec succès."]);
                $this->logger->info("Véhicule supprimé avec succès: " . $vehicle->getRegistrationNumber() .
                    " par l'utilisateur ID: " . $user->getUserId());
                exit;
            } else {
                throw new Exception("Erreur lors de la suppression du véhicule.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la suppression du véhicule: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Affiche les véhicules de l'utilisateur dans son espace
     */
    public function getUserCars(): void
    {
        header("Content-Type: application/json");
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Récupération des données l'utilisateur pour récupérer l'user_id nécessaire pour relier le véhicule à son propriétaire
            $user_id = $decodedToken->data->id;
            $user = User::find($user_id);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Récupération des véhicules de l'utilisateur
            $vehicles = Vehicle::getVehiclesByUserId($user->getUserId());
            $vehicleList = [];
            foreach ($vehicles as $vehicle) {
                $vehicleList[] = [
                    'vehicle_id' => $vehicle->getVehicleId(),
                    'brand' => $vehicle->getBrand(),
                    'model' => $vehicle->getModel(),
                    'registration_number' => $vehicle->getRegistrationNumber(),
                    'seating_capacity' => $vehicle->getSeatingCapacity(),
                    'color' => $vehicle->getColor(),
                    'energy_type' => $vehicle->getEnergyType(),
                    'first_service' => $vehicle->getFirstService()->format('Y-m-d'),
                ];
            }

            http_response_code(200);
            echo json_encode($vehicleList);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des véhicules de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }
}
