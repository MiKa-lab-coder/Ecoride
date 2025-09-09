<?php

namespace App\Controllers\TripController;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Validator;
use App\Services\TokenValidator;
use DateTime;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

/**
 * Class TripController
 * On y trouve la gestion des trajets, la création, suppression, modifications recherche, etc.
 * On y gère uniquement les actions que l'utilisateur peut faire concernant les trajets
 * Pour la sécurité, on gère le XSS dans le controller (htmlspecialchars), le JWT avec TokenValidator,
 * et la validation de format des données avec Validator
 * Les fonctionnalités de moderation (Moderator et admin) seront gérées dans AdminController.
 */
class TripController
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('trip_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/trip.log', 100));
    }

    // Proposer un trajet

    /**
     * Propose un nouveau trajet.
     * Exige un token JWT valide dans l'en-tête Authorization.
     * On vérifie que l'utilisateur a un véhicule enregistré
     */
    public function proposeTrip($data)
    {
        //on reçoit le token dans le header Authorization
        header('Content-Type: application/json');

        try {
            // Vérification du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            // Validaton du token JWT
            $tokenValidator = new TokenValidator();
            // Récupération de l'ID de l'utilisateur depuis le token
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            //recuperation et validation des données envoyées en JSON
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }
            //verification si requete POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            // Validation des champs obligatoires
            $requiredFields = [
                'trip_name', 'description', 'departure', 'arrival', 'trip_date',
                'departure_time', 'arrival_time', 'trip_price', 'available_seats',
                'smoking_allowed', 'pet_allowed', 'vehicle_id'
            ];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    throw new Exception("Le champ '{$field}' est obligatoire.", 400);
                }
            }

            // Validation des données
            $validator = new Validator();
            if (!$validator->validateTripName($data['trip_name'])) {
                throw new Exception("Nom de trajet invalide.", 400);
            }
            if (!$validator->validateDescription($data['description'])) {
                throw new Exception("Description invalide.", 400);
            }
            if (!$validator->validateDepartureOrArrival($data['departure'])) {
                throw new Exception("Lieu de départ invalide.", 400);
            }
            if (!$validator->validateDepartureOrArrival($data['arrival'])) {
                throw new Exception("Lieu d'arrivée invalide.", 400);
            }
            if (!$validator->validateTripDate($data['trip_date'])) {
                throw new Exception("Date et heure de départ invalide. Format attendu: d-m-Y, H:i.", 400);
            }
            if (!$validator->validateTripDate($data['departure_time'])) {
                throw new Exception("Date et heure de départ invalide. Format attendu: d-m-Y, H:i.", 400);
            }
            if (!$validator->validateTripDate($data['arrival_time'])) {
                throw new Exception("Date et heure d'arrivée invalide. Format attendu: d-m-Y, H:i.", 400);
            }
            if (!$validator->validateTripPrice((int)$data['trip_price'])) {
                throw new Exception("Prix du trajet invalide.", 400);
            }
            if (!$validator->validateSeatsAvailable((int)$data['available_seats'])) {
                throw new Exception("Nombre de sièges disponibles invalide.", 400);
            }
            if (!$validator->validatePetOrSmokingAllowed((int)$data['pet_allowed'], (int)$data['smoking_allowed'])) {
                throw new Exception("Valeur pour animaux ou fumeur invalide.", 400);
            }

            // On vérifie que le véhicule appartient bien à l'utilisateur
            $vehicleId = (int)$data['vehicle_id'];
            $userVehicles = Vehicle::getVehiclesByOwnerId($userId);
            $isVehicleOwned = false;
            foreach ($userVehicles as $vehicle) {
                if ($vehicle->getIdCar() === $vehicleId) {
                    $isVehicleOwned = true;
                    break;
                }
            }
            if (!$isVehicleOwned) {
                throw new Exception("Véhicule non trouvé ou n'appartient pas à l'utilisateur.", 403);
            }
            // Protection contre les attaques XSS
            $trip_name = htmlspecialchars($data['trip_name'], ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');
            $departure = htmlspecialchars($data['departure'], ENT_QUOTES, 'UTF-8');
            $arrival = htmlspecialchars($data['arrival'], ENT_QUOTES, 'UTF-8');
            $trip_date = htmlspecialchars($data['trip_date'], ENT_QUOTES, 'UTF-8');
            $departure_time = htmlspecialchars($data['departure_time'], ENT_QUOTES, 'UTF-8');
            $arrival_time = htmlspecialchars($data['arrival_time'], ENT_QUOTES, 'UTF-8');
            $trip_price = (int)$data['trip_price'];
            $available_seats = (int)$data['available_seats'];
            $smoking_allowed = (int)$data['smoking_allowed'];
            $pet_allowed = (int)$data['pet_allowed'];
            $vehicle_id = htmlspecialchars($data['vehicle_id'], ENT_QUOTES, 'UTF-8');
            $userId = $user->getIdUser();// Récupération de l'ID de l'utilisateur depuis le token

            // Création du trajet
            $trip = new Trip(
                $trip_name,
                $description,
                $departure,
                $arrival,
                new DateTime($trip_date . ' ' . $departure_time),
                new DateTime($trip_date . ' ' . $arrival_time),
                (int)$data['trip_price'],
                (int)$data['available_seats'],
                (bool)$data['pet_allowed'],
                (bool)$data['smoking_allowed'],
                (int)$data['vehicle_id'],
                $userId
            );
            // Enregistrement du trajet en base de données
            if ($trip->save()) {
                http_response_code(201);
                echo json_encode(["message" => "Trajet proposé avec succès."]);
                $this->logger->info("Trajet proposé avec succès: " . $trip_name . " par l'utilisateur ID: " . $userId);
                exit;
            } else {
                throw new Exception("Erreur lors de l'enregistrement du trajet.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la proposition de trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }

    // Modifier un trajet
    public function updateTrip(int $tripId)
    {
        header('Content-Type: application/json');
        try {
            // Vérification du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            // Validaton du token JWT
            $tokenValidator = new TokenValidator();
            // Récupération de l'ID de l'utilisateur depuis le token
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            //recuperation et validation des données envoyées en JSON
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }
            //verification si requete POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            // Récupération du trajet
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // Vérification que l'utilisateur est le conducteur du trajet
            if ($trip->getIdUser() !== $userId) {
                throw new Exception("Vous n'êtes pas autorisé à modifier ce trajet.", 403);
            }
            // Validation des données
            $validator = new Validator();

            if (isset($data['trip_name'])) {
                if (!$validator->validateTripName($data['trip_name'])) {
                    throw new Exception("Nom de trajet invalide.", 400);
                }
                $trip->setTripName(htmlspecialchars($data['trip_name'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($data['description'])) {
                if (!$validator->validateDescription($data['description'])) {
                    throw new Exception("Description invalide.", 400);
                }
                $trip->setTripDescription(htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($data['departure'])) {
                if (!$validator->validateDepartureOrArrival($data['departure'])) {
                    throw new Exception("Lieu de départ invalide.", 400);
                }
                $trip->setDepartureLocation(htmlspecialchars($data['departure'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($data['arrival'])) {
                if (!$validator->validateDepartureOrArrival($data['arrival'])) {
                    throw new Exception("Lieu d'arrivée invalide.", 400);
                }
                $trip->setArrivalLocation(htmlspecialchars($data['arrival'], ENT_QUOTES, 'UTF-8'));
            }
            // Gestion des dates et heures
            if (isset($data['trip_date']) && isset($data['departure_time'])) {
                if (!$validator->validateTripDate($data['trip_date']) || !$validator->validateTime($data['departure_time'])) {
                    throw new Exception("Date ou heure de départ invalide. Formats attendus: Y-m-d et H:i.", 400);
                }
                $departureDateTime = new DateTime($data['trip_date'] . ' ' . $data['departure_time']);
                if ($departureDateTime < new DateTime()) {
                    throw new Exception("La date de départ ne peut pas être dans le passé.", 400);
                }
                $trip->setDepartureDateTime($departureDateTime);
            }
            if (isset($data['trip_date']) && isset($data['arrival_time'])) {
                if (!$validator->validateTripDate($data['trip_date']) || !$validator->validateTime($data['arrival_time'])) {
                    throw new Exception("Date ou heure d'arrivée invalide. Formats attendus: Y-m-d et H:i.", 400);
                }
                $arrivalDateTime = new DateTime($data['trip_date'] . ' ' . $data['arrival_time']);
                $trip->setArrivalDateTime($arrivalDateTime);
            }

            if (isset($data['trip_price'])) {
                if (!$validator->validateTripPrice((int)$data['trip_price'])) {
                    throw new Exception("Prix du trajet invalide.", 400);
                }
                $trip->setTripPrice((int)$data['trip_price']);
            }
            if (isset($data['available_seats'])) {
                if (!$validator->validateSeatsAvailable((int)$data['available_seats'])) {
                    throw new Exception("Nombre de sièges disponibles invalide.", 400);
                }
                $trip->setSeatsAvailable((int)$data['available_seats']);
            }
            if (isset($data['pet_allowed'])) {
                $trip->setPetAllowed((bool)$data['pet_allowed']);
            }
            if (isset($data['smoking_allowed'])) {
                $trip->setSmokingAllowed((bool)$data['smoking_allowed']);
            }

            // Enregistrement des modifications
            if ($trip->save()) {
                ;
                http_response_code(200);
                echo json_encode(["message" => "Trajet modifié avec succès."]);
                $this->logger->info("Trajet ID: " . $tripId . " modifié par l'utilisateur ID: " . $userId);
                exit;
            } else {
                throw new Exception("Erreur lors de la modification du trajet.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la modification du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }

    // Supprimer un trajet
    public function deleteTrip($tripId)
    {
        header('Content-Type: application/json');

        try {
            // Vérification du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            //verification si requete DELETE
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            // Récupération du trajet
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // Vérification que l'utilisateur est le conducteur du trajet
            if ($trip->getIdUser() !== $userId) {
                throw new Exception("Vous n'êtes pas autorisé à supprimer ce trajet.", 403);
            }
            // Suppression du trajet
            if ($trip->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet supprimé avec succès."]);
                $this->logger->info("Trajet ID: " . $tripId . " supprimé par l'utilisateur ID: " . $userId);
                exit;
            } else {
                throw new Exception("Erreur lors de la suppression du trajet.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la suppression du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }

    // Récupérer le nombre de places disponibles pour un trajet
    public function getAvailableSeats($tripId)
    {
        header('Content-Type: application/json');

        try {
            // Vérification du token
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            //verification si requete GET
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            // Récupération du trajet
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // Récupération du nombre de places disponibles
            $availableSeats = $trip->calculateRemainingSeats();
            http_response_code(200);
            echo json_encode(["available_seats" => $availableSeats]);
            exit;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des places disponibles: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }

    // rechercher des trajets (avec filtres optionnels)
    public function searchWithFiltersOrNot(): void
    {
        header('Content-Type: application/json');

        //Récupération des paramètres de recherche obligatoires
        $departure = $_GET['departure'] ?? null;
        $arrival = $_GET['arrival'] ?? null;
        $trip_date = $_GET['trip_date'] ?? null;

        // Validation des paramètres obligatoires
        if ($departure && $arrival && $trip_date) {
            http_response_code(400);
            echo json_encode(['error' => "Veuillez remplir les champs de départ, d'arrivée et la date pour effectuer une recherche."]);
            return;
        }
        try {
            $departureDate = new DateTime($trip_date);

            $trips = Trip::searchByDepartureLocation($departure);

            if ($trips !== null) {
                $trips = array_filter($trips, function ($trip) use ($arrival) {
                    return str_contains($trip->getArrivalLocation(), $arrival);
                });

                $trips = array_filter($trips, function ($trip) use ($departureDate) {
                    return $trip->getDepartureDateTime()->format('Y-m-d') === $departureDate->format('Y-m-d');
                });
            }
            // Filtres optionnels
            //Plutôt que de lancer de nouvelles requêtes, on filtre les résultats déjà obtenus
            if (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) {
                $maxPrice = (int)$_GET['max_price'];
                $trips = array_filter($trips, function ($trip) use ($maxPrice) {
                    return $trip->getTripPrice() <= $maxPrice;
                });
            }
            if (isset($_GET['min_seats']) && is_numeric($_GET['min_seats'])) {
                $minSeats = (int)$_GET['min_seats'];
                $trips = array_filter($trips, function ($trip) use ($minSeats) {
                    return $trip->calculateRemainingSeats() >= $minSeats;
                });
            }
            if (isset($_GET['pet_allowed'])) {
                $trips = array_filter($trips, fn($trip) => $trip->isPetAllowed() === true);
            }
            if (isset($_GET['smoking_allowed'])) {
                $trips = array_filter($trips, fn($trip) => $trip->isSmokingAllowed() === true);
            }
            if (isset($_GET['eco_friendly'])) {
                $trips = array_filter($trips, function ($trip) {
                    return in_array($trip->getEnergyType(), ['electric', 'hybrid']);
                });
            }
            if (isset($_GET['driver_rating']) && is_numeric($_GET['driver_rating'])) {
                $trips = Trip::searchByDriverRating((int)$_GET['driver_rating']);
            }

            if (empty($trips)) {
                http_response_code(200);
                echo json_encode(['message' => "Aucun trajet ne correspond à vos critères de recherche."]);
                return;
            }

            http_response_code(200);
            $tripsArray = array_map(fn($trip) => $trip->toArray(), $trips);
            echo json_encode($tripsArray);


        } catch (Exception $e) {
            $logger = new Logger('trip_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/trip.log', 400));
            $logger->error("Erreur lors de la recherche de trajets: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => "Une erreur est survenue. Veuillez vérifier vos critères et réessayer."]);
            return;
        }
    }
}

