<?php

namespace App\Controllers\VehicleController;

use App\Models\Vehicle;
use DateTime;
use App\Models\User;
use App\Services\Validator;
use App\Services\TokenManager;
use App\Services\TokenValidator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

/**
 * Class VehicleController
 * Gere les actions liées aux véhicules.
 * On peut ajouter, modifier, supprimer et afficher les véhicules.
 * Pour la sécurité, on gere le XSS dans le controller (htmlspecialchars), le JWT avec TokenValidator,
 *  et la validation de format des données avec Validator
 */
class VehicleController
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('vehicle_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/vehicle.log', 100));
    }

    // Ajouter un véhicule
    public function addCar(): void
    {
        header("Content-Type: application/json");
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Récupération des données l'utilisateur pour récupérer l'id_user nécessaire pour relier le véhicule à son propriétaire
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Récupération et validation des données envoyées en JSON
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }

            // Verification des champs obligatoires
            if (empty($data['registration'])
                || empty($data['first_service'])
                || empty($data['brand'])
                || empty($data['model'])
                || empty($data['color'])
                || empty($data['seat_capacity'])
                || empty($data['energy'])) {
                throw new Exception("Tous les champs obligatoires doivent être remplis.", 400);
            }
            // Validation des données
            $validator = new Validator();
            if (!$validator->validateRegistrationNumber($data['registration'])) {
                throw new Exception("Numéro d'immatriculation invalide.", 400);
            }
            if (!$validator->validateDateFormat($data['first_service'])) {
                throw new Exception("Date de première mise en circulation invalide. Format attendu: d-m-Y.", 400);
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
            if (!$validator->validateSeatCapacity($data['seat_capacity'])) {
                throw new Exception("Capacité d'accueil invalide. Doit être un nombre entier entre 1 et 9.", 400);
            }
            if (!$validator->validateEnergyType($data['energy'])) {
                throw new Exception("Type d'énergie invalide.", 400);
            }
            // Protection contre les attaques XSS
            $registration = htmlspecialchars($data['registration'], ENT_QUOTES, 'UTF-8');
            $first_service = htmlspecialchars($data['first_service'], ENT_QUOTES, 'UTF-8');
            $brand = htmlspecialchars($data['brand'], ENT_QUOTES, 'UTF-8');
            $model = htmlspecialchars($data['model'], ENT_QUOTES, 'UTF-8');
            $color = htmlspecialchars($data['color'], ENT_QUOTES, 'UTF-8');
            $seat_capacity = (int)$data['seat_capacity'];
            $energy = htmlspecialchars($data['energy'], ENT_QUOTES, 'UTF-8');
            $owner_id = $user->getIdUser();

            // vérification que la date de première mise en circulation n'est pas dans le futur
            $date_first_service = new DateTime($first_service);
            $now = new DateTime();
            if ($date_first_service > $now) {
                throw new Exception("La date de première mise en circulation ne peut pas être dans le futur.", 400);
            }

            // Verification que le numéro d'immatriculation est unique (donc que le véhicule n'existe pas déjà)
            $existingVehicle = Vehicle::findByRegistration($registration);
            if ($existingVehicle) {
                throw new Exception("Un véhicule avec ce numéro d'immatriculation existe déjà.", 400);
            }

            // Création du véhicule
            $vehicle = new Vehicle(
                $brand,
                $model,
                $registration,
                $seat_capacity,
                $color,
                $energy,
                $date_first_service,
                $owner_id
            );
            // Enregistrement du véhicule en base de données
            if ($vehicle->save()) {
                http_response_code(201);
                echo json_encode(["message" => "Véhicule ajouté avec succès."]);
                $this->logger->info("Véhicule ajouté avec succès: " . $registration . " par l'utilisateur ID: " . $owner_id);
                exit;
            } else {
                throw new Exception("Erreur lors de l'ajout du véhicule.", 500);
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'ajout du véhicule: " . $e->getMessage());
        }
        http_response_code($e->getCode() ?: 500);
        echo json_encode(["error" => $e->getMessage()]);
        exit;
    }

    // supprimer un véhicule
    public function deleteCar(): void
    {
        header("Content-Type: application/json");
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Récupération des données l'utilisateur pour récupérer l'id_user nécessaire pour relier le véhicule à son propriétaire
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Récupération et validation des données envoyées en JSON
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }

            // Verification du champ obligatoire
            if (empty($data['registration'])) {
                throw new Exception("Le champ immatriculation est obligatoire.", 400);
            }
            // Validation des données
            $validator = new Validator();
            if (!$validator->validateRegistrationNumber($data['registration'])) {
                throw new Exception("Numéro d'immatriculation invalide.", 400);
            }
            // Protection contre les attaques XSS
            $registration = htmlspecialchars($data['registration'], ENT_QUOTES, 'UTF-8');

            // Vérification que le véhicule existe
            $vehicle = Vehicle::findByRegistration($registration);
            if (!$vehicle) {
                throw new Exception("Véhicule non trouvé.", 404);
            }
            // Vérification que l'utilisateur est bien le propriétaire du véhicule
            if ($vehicle->getOwnerId() !== $user->getIdUser()) {
                throw new Exception("Vous n'êtes pas autorisé à supprimer ce véhicule.", 403);
            }
            // Suppression du véhicule
            if ($vehicle->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "Véhicule supprimé avec succès."]);
                $this->logger->info("Véhicule supprimé avec succès: " . $registration .
                    " par l'utilisateur ID: " . $user->getIdUser());
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
}
