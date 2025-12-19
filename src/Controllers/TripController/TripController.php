<?php

namespace App\Controllers\TripController;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Validator;
use App\Services\TokenValidator;
use App\Services\Mailler;
use DateTime;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

class TripController
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('trip_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/trip.log', Logger::INFO));
    }

    public function proposeTrip(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) throw new Exception("Données invalides.", 400);

            $requiredFields = ['departure_day', 'arrival_day', 'departure_location', 'arrival_location', 'departure_time', 'arrival_time', 'trip_price', 'seating', 'vehicle_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Le champ '{$field}' est obligatoire.", 400);
                }
            }
            
            $userVehicles = Vehicle::getVehiclesByUserId($userId);
            if (empty($userVehicles)) throw new Exception("Vous devez enregistrer un véhicule avant de proposer un trajet.", 400);

            $vehicleId = (int)$data['vehicle_id'];
            $isVehicleOwned = false;
            foreach ($userVehicles as $vehicle) {
                if ($vehicle->getVehicleId() === $vehicleId) {
                    $isVehicleOwned = true;
                    break;
                }
            }
            if (!$isVehicleOwned) throw new Exception("Véhicule non trouvé ou n'appartient pas à l'utilisateur.", 403);

            $fuelType = Vehicle::getFuelTypeById($vehicleId);
            if (!$fuelType) throw new Exception("Véhicule non trouvé.", 404);

            $tripNature = in_array(strtolower($fuelType), ['electric', 'hybrid']) ? 'ecologic' : 'standard';

            $departureDay = new DateTime($data['departure_day']);
            $arrivalDay = new DateTime($data['arrival_day']);
            $departureTime = new DateTime($data['departure_time']);
            $arrivalTime = new DateTime($data['arrival_time']);
            $trip_time = ($arrivalTime->getTimestamp() - $departureTime->getTimestamp()) / 60;

            $trip = new Trip(
                $departureDay,
                $arrivalDay,
                htmlspecialchars($data['departure_location'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($data['arrival_location'], ENT_QUOTES, 'UTF-8'),
                $departureTime,
                $arrivalTime,
                (int)$trip_time,
                (int)$data['trip_price'],
                $tripNature,
                (bool)($data['animal_pref'] ?? false),
                (bool)($data['smoking_pref'] ?? false),
                (int)$data['seating'],
                'pending',
                $userId,
                $vehicleId
            );

            if ($trip->save()) {
                http_response_code(201);
                echo json_encode(["message" => "Trajet proposé avec succès."]);
            } else {
                throw new Exception("Erreur lors de l'enregistrement du trajet.", 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function updateTrip(int $tripId): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;

            $trip = Trip::findById($tripId);
            if (!$trip || $trip->getDriverId() !== $userId) {
                throw new Exception("Trajet non trouvé ou non autorisé.", 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) throw new Exception("Données invalides.", 400);

            // ... (logique de mise à jour)

            if ($trip->save()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet modifié avec succès."]);
            } else {
                throw new Exception("Erreur lors de la modification du trajet.", 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function deleteTrip(int $tripId): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;

            $trip = Trip::findById($tripId);
            if (!$trip || $trip->getDriverId() !== $userId) {
                throw new Exception("Trajet non trouvé ou non autorisé.", 404);
            }

            if ($trip->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet supprimé avec succès."]);
            } else {
                throw new Exception("Erreur lors de la suppression du trajet.", 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function startTrip(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['trip_id'])) throw new Exception("ID de trajet invalide.", 400);
            
            $tripId = (int)$data['trip_id'];
            $trip = Trip::findById($tripId);

            if (!$trip || $trip->getDriverId() !== $userId) {
                throw new Exception("Trajet non trouvé ou non autorisé.", 404);
            }
            
            if ($trip->getStatus() !== 'approved') {
                throw new Exception("Le trajet ne peut pas être démarré (statut: " . $trip->getStatus() . ").", 400);
            }

            $trip->setStatus('ongoing');
            if ($trip->save()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet démarré avec succès."]);
            } else {
                throw new Exception("Erreur lors du démarrage du trajet.", 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function getUserTrips(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;

            $trips = Trip::getTripsByDriverId($userId);
            
            $tripsArray = array_map(fn($trip) => $trip->toArray(), $trips);
            
            http_response_code(200);
            echo json_encode($tripsArray);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des trajets de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
