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

/**
 * Class TripController
 * Gère les actions liées aux trajets : création, suppression, modification, recherche.
 * Pour la sécurité, gère le XSS, le JWT et la validation des données.
 */
class TripController
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('trip_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/trip.log', Logger::INFO));
    }

    /**
     * Propose un nouveau trajet.
     * Exige un token JWT valide dans l'en-tête Authorization.
     * On vérifie que l'utilisateur a un véhicule enregistré.
     */
    public function proposeTrip(): void
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }

            // Validation des champs obligatoires
            $requiredFields = [
                'departure_day', 'arrival_day', 'departure_location', 'arrival_location',
                'departure_time', 'arrival_time', 'trip_time', 'trip_price',
                'animal_pref', 'smoking_pref', 'seating', 'vehicle_id'
            ];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Le champ '{$field}' est obligatoire.", 400);
                }
            }

            // On vérifie que le véhicule appartient bien à l'utilisateur et qu'il en a au moins un
            $userVehicles = Vehicle::getVehiclesByUserId($userId);
            if (empty($userVehicles)) {
                throw new Exception("Vous devez enregistrer un véhicule avant de proposer un trajet.", 400);
            }

            $vehicleId = (int)$data['vehicle_id'];
            $isVehicleOwned = false;
            foreach ($userVehicles as $vehicle) {
                if ($vehicle->getVehicleId() === $vehicleId) {
                    $isVehicleOwned = true;
                    break;
                }
            }
            if (!$isVehicleOwned) {
                throw new Exception("Véhicule non trouvé ou n'appartient pas à l'utilisateur.", 403);
            }

            // On obtient le type d'énergie du véhicule pour déterminer le type de trajet
            $fuelType = Vehicle::getFuelTypeById($vehicleId);
            if (!$fuelType) {
                throw new Exception("Véhicule non trouvé.", 404);
            }

            // Définition de la nature du trajet de manière automatique
            // La nature est définie par le code, pas par l'entrée utilisateur
            $tripNature = in_array(strtolower($fuelType), ['electric', 'hybrid']) ? 'ecologic' : 'standard';

            // Instanciation unique du validateur
            $validator = new Validator();

            // Validation des formats de date et heure
            if (!$validator->validateTripDate($data['departure_day'], 'Y-m-d')) {
                throw new Exception("Le format de la date de départ est invalide. Utilisez 'Y-m-d'.", 400);
            }
            if (!$validator->validateTripDate($data['arrival_day'], 'Y-m-d')) {
                throw new Exception("Le format de la date d'arrivée est invalide. Utilisez 'Y-m-d'.", 400);
            }
            if (!$validator->validateTime($data['departure_time'], 'H:i')) {
                throw new Exception("Le format de l'heure de départ est invalide. Utilisez 'H:i'.", 400);
            }
            if (!$validator->validateTime($data['arrival_time'], 'H:i')) {
                throw new Exception("Le format de l'heure d'arrivée est invalide. Utilisez 'H:i'.", 400);
            }

            // Validation des autres champs
            if (!$validator->validateDepartureOrArrival($data['departure_location'])) {
                throw new Exception("Le format du lieu de départ est invalide.", 400);
            }
            if (!$validator->validateDepartureOrArrival($data['arrival_location'])) {
                throw new Exception("Le format du lieu d'arrivée est invalide.", 400);
            }
            if (!$validator->validateTripPrice((int)$data['trip_price'])) {
                throw new Exception("Le prix du trajet doit être un entier positif.", 400);
            }
            if (!$validator->validateSeatsAvailable((int)$data['seating'])) {
                throw new Exception("Le nombre de sièges disponibles doit être un entier positif entre 1 et 9.", 400);
            }
            if (!$validator->validatePetAllowed((int)$data['animal_pref'])) {
                throw new Exception("La préférence pour les animaux doit être 0 ou 1.", 400);
            }
            if (!$validator->validateSmokingAllowed((int)$data['smoking_pref'])) {
                throw new Exception("La préférence pour le tabac doit être 0 ou 1.", 400);
            }

            // Création du trajet
            $departureDay = new DateTime($data['departure_day']);
            $arrivalDay = new DateTime($data['arrival_day']);
            $departureTime = new DateTime($data['departure_time']);
            $arrivalTime = new DateTime($data['arrival_time']);
            $trip = new Trip(
                $departureDay,
                $arrivalDay,
                htmlspecialchars($data['departure_location'], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($data['arrival_location'], ENT_QUOTES, 'UTF-8'),
                $departureTime,
                $arrivalTime,
                (int)$data['trip_time'],
                (int)$data['trip_price'],
                $tripNature, // Utilisation de la variable calculée
                (bool)$data['animal_pref'],
                (bool)$data['smoking_pref'],
                (int)$data['seating'],
                'pending',// Statut initial 'pending' en attendant validation modérateur
                $userId,
                $vehicleId
            );

            // Enregistrement du trajet en base de données
            if ($trip->save()) {
                http_response_code(201);
                echo json_encode(["message" => "Trajet proposé avec succès."]);
                $this->logger->info("Trajet proposé avec succès par l'utilisateur ID: " . $userId);
            } else {
                throw new Exception("Erreur lors de l'enregistrement du trajet.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la proposition de trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    // Méthode pour modifier un trajet existant
    public function updateTrip(int $tripId): void
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }
            // On vérifie que le trajet existe
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // On vérifie que l'utilisateur est bien le propriétaire du trajet
            if ($trip->getDriverId() !== $userId) {
                throw new Exception("Vous n'êtes pas autorisé à modifier ce trajet.", 403);
            }

            // Création d'une instance de Validator
            $validator = new Validator();

            // On met à jour les champs s'ils sont présents dans la requête et valide les données
            if (isset($data['departure_day'])) {
                if (!$validator->validateTripDate($data['departure_day'], 'Y-m-d')) {
                    throw new Exception("Le format de la date de départ est invalide. Utilisez 'Y-m-d'.", 400);
                }
                $trip->setDepartureDay(new DateTime($data['departure_day']));
            }
            if (isset($data['arrival_day'])) {
                if (!$validator->validateTripDate($data['arrival_day'], 'Y-m-d')) {
                    throw new Exception("Le format de la date d'arrivée est invalide. Utilisez 'Y-m-d'.", 400);
                }
                $trip->setArrivalDay(new DateTime($data['arrival_day']));
            }
            if (isset($data['departure_location'])) {
                if (!$validator->validateDepartureOrArrival($data['departure_location'])) {
                    throw new Exception("Le format du lieu de départ est invalide.", 400);
                }
                $trip->setDepartureLocation(htmlspecialchars($data['departure_location'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($data['arrival_location'])) {
                if (!$validator->validateDepartureOrArrival($data['arrival_location'])) {
                    throw new Exception("Le format du lieu d'arrivée est invalide.", 400);
                }
                $trip->setArrivalLocation(htmlspecialchars($data['arrival_location'], ENT_QUOTES, 'UTF-8'));
            }
            if (isset($data['departure_time'])) {
                if (!$validator->validateTime($data['departure_time'], 'H:i')) {
                    throw new Exception("Le format de l'heure de départ est invalide. Utilisez 'H:i'.", 400);
                }
                $trip->setDepartureTime(new DateTime($data['departure_time']));
            }
            if (isset($data['arrival_time'])) {
                if (!$validator->validateTime($data['arrival_time'], 'H:i')) {
                    throw new Exception("Le format de l'heure d'arrivée est invalide. Utilisez 'H:i'.", 400);
                }
                $trip->setArrivalTime(new DateTime($data['arrival_time']));
            }
            if (isset($data['trip_time'])) {
                // Pas de validation spécifique pour trip_time, on s'assure que c'est un entier.
                $trip->setTripTime((int)$data['trip_time']);
            }
            if (isset($data['trip_price'])) {
                if (!$validator->validateTripPrice((int)$data['trip_price'])) {
                    throw new Exception("Le prix du trajet doit être un entier positif.", 400);
                }
                $trip->setTripPrice((int)$data['trip_price']);
            }
            if (isset($data['animal_pref'])) {
                if (!$validator->validatePetAllowed((int)$data['animal_pref'])) {
                    throw new Exception("La préférence pour les animaux doit être 0 ou 1.", 400);
                }
                $trip->setAnimalPref((bool)$data['animal_pref']);
            }
            if (isset($data['smoking_pref'])) {
                if (!$validator->validateSmokingAllowed((int)$data['smoking_pref'])) {
                    throw new Exception("La préférence pour le tabac doit être 0 ou 1.", 400);
                }
                $trip->setSmokingPref((bool)$data['smoking_pref']);
            }
            if (isset($data['seating'])) {
                if (!$validator->validateSeatsAvailable((int)$data['seating'])) {
                    throw new Exception("Le nombre de sièges disponibles doit être un entier positif entre 1 et 9.", 400);
                }
                $trip->setSeating((int)$data['seating']);
            }
            if (isset($data['vehicle_id'])) {
                // On vérifie que le véhicule appartient bien à l'utilisateur
                $vehicleId = (int)$data['vehicle_id'];
                $userVehicles = Vehicle::getVehiclesByUserId($userId);
                $isVehicleOwned = false;
                foreach ($userVehicles as $vehicle) {
                    if ($vehicle->getVehicleId() === $vehicleId) {
                        $isVehicleOwned = true;
                        break;
                    }
                }
                if (!$isVehicleOwned) {
                    throw new Exception("Véhicule non trouvé ou n'appartient pas à l'utilisateur.", 403);
                }
                $trip->setVehicleId((int)$data['vehicle_id']);
            }
            // On met a jour la nature du trajet si le véhicule a changé
            $fuelType = Vehicle::getFuelTypeById($vehicleId);
            if ($fuelType) {
                $tripNature = in_array(strtolower($fuelType), ['electric', 'hybrid']) ? 'ecologic' : 'standard';
                $trip->setTripNature($tripNature);
            }
            if ($trip->save()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet modifié avec succès."]);
                $this->logger->info("Trajet ID: " . $tripId . " modifié par l'utilisateur ID: " . $userId);
            } else {
                throw new Exception("Erreur lors de la modification du trajet.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la modification du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    // Méthode pour supprimer un trajet existant
    public function deleteTrip(int $tripId): void
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // On vérifie que le trajet existe
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // On vérifie que l'utilisateur est bien le propriétaire du trajet
            if ($trip->getDriverId() !== $userId) {
                throw new Exception("Vous n'êtes pas autorisé à supprimer ce trajet.", 403);
            }

            if ($trip->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet supprimé avec succès."]);
                $this->logger->info("Trajet ID: " . $tripId . " supprimé par l'utilisateur ID: " . $userId);
            } else {
                throw new Exception("Erreur lors de la suppression du trajet.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la suppression du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Récupère le nombre de places disponibles pour un trajet.
     */
    public function getAvailableSeats(int $tripId): void
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            // On vérifie que le trajet existe
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // On calcule le nombre de places disponibles
            $availableSeats = $trip->calculateRemainingSeats();
            http_response_code(200);
            echo json_encode(["available_seats" => $availableSeats]);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des places disponibles: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Recherche des trajets avec ou sans filtres.
     */
    public function searchWithFiltersOrNot(): void
    {
        header('Content-Type: application/json');

        // Création d'une instance de Validator pour les vérifications
        $validator = new Validator();

        try {
            // Champs obligatoires
            $departure = htmlspecialchars($_GET['departure'] ?? '');
            $arrival = htmlspecialchars($_GET['arrival'] ?? '');
            $date = htmlspecialchars($_GET['date'] ?? '');

            // Validation des champs obligatoires
            if (!$validator->validateDepartureOrArrival($departure)) {
                throw new Exception("Le format du lieu de départ est invalide ou manquant.", 400);
            }
            if (!$validator->validateDepartureOrArrival($arrival)) {
                throw new Exception("Le format du lieu d'arrivée est invalide ou manquant.", 400);
            }
            if (!$validator->validateYmdDateFormat($date)) {
                throw new Exception("Le format de la date de départ est invalide ou manquant. Utilisez 'Y-m-d'.", 400);
            }

            // On met les filtres optionnels
            $filters = [];

            if (isset($_GET['price'])) {
                if (!$validator->validateTripPrice((int)$_GET['price'])) {
                    throw new Exception("Le prix maximum doit être un entier positif.", 400);
                }
                $filters['max_price'] = (int)$_GET['price'];
            }

            if (isset($_GET['carpoolType']) && $_GET['carpoolType'] !== 'all') {
                if (!$validator->validateTripNature($_GET['carpoolType'])) {
                    throw new Exception("Le format de la nature du trajet est invalide.", 400);
                }
                $filters['trip_nature'] = htmlspecialchars($_GET['carpoolType']);
            }

            if (isset($_GET['seats'])) {
                if (!$validator->validateSeatsAvailable((int)$_GET['seats'])) {
                    throw new Exception("Le nombre de sièges doit être un entier positif entre 1 et 9.", 400);
                }
                $filters['seating'] = (int)$_GET['seats'];
            }

            if (isset($_GET['petsAllowed'])) {
                $filters['animal_pref'] = $_GET['petsAllowed'] === 'yes' ? 1 : 0;
            }

            if (isset($_GET['smokingAllowed'])) {
                $filters['smoking_pref'] = $_GET['smokingAllowed'] === 'yes' ? 1 : 0;
            }

            // On recherche les trajets
            $trips = Trip::searchTrips($departure, $arrival, $date, $filters);

            if (empty($trips)) {
                http_response_code(200);
                echo json_encode(['message' => "Aucun trajet ne correspond à vos critères de recherche."]);
                return;
            }

            http_response_code(200);
            $tripsArray = array_map(fn($trip) => $trip->toArray(), $trips);
            echo json_encode($tripsArray);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la recherche de trajets: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Finalise un trajet (lorsque le conducteur clique sur "Terminer le trajet").
     */
    public function endTrip(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['trip_id'])) {
                throw new Exception("ID de trajet invalide.", 400);
            }
            $tripId = (int)$data['trip_id'];
            // On vérifie que le trajet existe
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // On vérifie que l'utilisateur est bien le propriétaire du trajet
            if ($trip->getDriverId() !== $userId) {
                throw new Exception("Vous n'êtes pas autorisé à terminer ce trajet.", 403);
            }
            // On vérifie que le trajet n'est pas déjà terminé
            if ($trip->getStatus() === 'completed') {
                throw new Exception("Le trajet est déjà terminé.", 400);
            }
            // On met à jour le statut du trajet
            $trip->setStatus('completed');

            // On envoie un email à chaque passager pour l'informer que le trajet est terminé
            $mailler = new Mailler();
            $passengers = $trip->getPassengers();
            foreach ($passengers as $passenger) {
                $mailler->sendEndRideMail(
                    $passenger->getEmail(),
                    $passenger->getFirstName()
                );
            }
            // On change le statut du trajet en 'completed'
            $trip->setStatus('completed');
            if ($trip->save()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet terminé avec succès."]);
                $this->logger->info("Trajet ID: " . $tripId . " terminé par l'utilisateur ID: " . $userId);
            } else {
                throw new Exception("Erreur lors de la finalisation du trajet.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la finalisation du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Affiche les détails d'un trajet pour le conducteur et les passagers.
     */
    public function getTripDetails(int $tripId): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // On vérifie que le trajet existe
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // On vérifie que l'utilisateur est bien le conducteur
            $isDriver = $trip->getDriverId() === $userId;
            $isPassenger = false;
            $passengers = $trip->getPassengers();
            foreach ($passengers as $passenger) {
                if ($passenger->getUserId() === $userId) {
                    $isPassenger = true;
                    break;
                }
            }
            if (!$isDriver && !$isPassenger) {
                throw new Exception("Vous n'êtes pas autorisé à voir les détails de ce trajet.", 403);
            }

            http_response_code(200);
            echo json_encode($trip->toArray());
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des détails du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    // Méthode pour récupérer tous les trajets d'un utilisateur (conducteur)
    public function getUserTrips(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // On récupère les trajets de l'utilisateur
            $trips = Trip::getTripsByDriverId($userId);
            if (empty($trips)) {
                http_response_code(200);
                echo json_encode(['message' => "Vous n'avez proposé aucun trajet."]);
                return;
            }
            http_response_code(200);
            $tripsArray = array_map(fn($trip) => $trip->toArray(), $trips);
            echo json_encode($tripsArray);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des trajets de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Méthode pour récupérer tous les trajets terminés pour affichage dans l'historique d'un utilisateur
    public function getUserCompletedTrips(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // On récupère les trajets terminés de l'utilisateur
            $trips = Trip::getCompletedTripsByUser($userId);
            if (empty($trips)) {
                http_response_code(200);
                echo json_encode(['message' => "Vous n'avez aucun trajet terminé."]);
                return;
            }
            http_response_code(200);
            $tripsArray = array_map(fn($trip) => $trip->toArray(), $trips);
            echo json_encode($tripsArray);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des trajets terminés de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Méthode pour démarrer un trajet (lorsque le conducteur clique sur "Démarrer le trajet")
    public function startTrip(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['trip_id'])) {
                throw new Exception("ID de trajet invalide.", 400);
            }
            $tripId = (int)$data['trip_id'];
            // On vérifie que le trajet existe
            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            // On verifie l'utilisateur qui demarre le trajet est bien celui qui l'a proposé
            if ($trip->getDriverId() !== $userId) {
                throw new Exception("Vous n'êtes pas autorisé à démarrer ce trajet.", 403);
            }
            // On vérifie que le trajet n'est pas déjà démarré ou terminé
            if ($trip->getStatus() !== 'ongoing' && $trip->getStatus() !== 'completed') {
                throw new Exception("Le trajet a un statut incorrect pour être démarré.", 400);
            }
            // On met à jour le statut du trajet
            $trip->setStatus('ongoing');
            if ($trip->save()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet démarré avec succès."]);
                $this->logger->info("Trajet ID: " . $tripId . " démarré par l'utilisateur ID: " . $userId);
            } else {
                throw new Exception("Erreur lors du démarrage du trajet.", 500);
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur lors du démarrage du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Méthode pour récupérer tous les trajets effectués sur les 7 derniers jours
    public function getWeeklyTrips(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->sub;
            // On vérifie que l'existence et le rôle de l'utilisateur seul admin peut accéder à cette ressource
            $user = User::find($userId);
            if (!$user || $user->getRoleId() !== 1) {
                throw new Exception("Utilisateur non trouvé ou non autorisé.", 404);
            }
            // On récupère les trajets de la semaine
            $trips = Trip::getTripsCountLast7Days();
            if (empty($trips)) {
                http_response_code(200);
                echo json_encode(['message' => "Aucun trajet n'a été effectué cette semaine."]);
                return;
            }
            http_response_code(200);
            echo json_encode($trips);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des trajets de la semaine: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
