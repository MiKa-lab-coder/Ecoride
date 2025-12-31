<?php

namespace App\Controllers\TripController;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Review;
use App\Models\Booking;
use App\Models\Transaction;
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
    private const PLATFORM_FEE = 2;

    public function __construct()
    {
        $this->logger = new Logger('trip_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/trip.log', Logger::INFO));
    }

    /**
     * Proposer un trajet
     */
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

            $requiredFields = ['departure_day', 'arrival_day', 'departure_location', 'arrival_location', 'departure_time',
                'arrival_time', 'trip_price', 'seating', 'vehicle_id'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Le champ '{$field}' est obligatoire.", 400);
                }
            }
            // recuperation des vehicules de l'utilisateur
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

    /**
     * Modifier un trajet
     */
    public function updateTrip(): void
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

            // Gestion des réservations existantes : Remboursement et annulation
            $bookings = Booking::findByTripId($tripId);
            if (!empty($bookings)) {
                $mailler = new Mailler();
                foreach ($bookings as $booking) {
                    // Remboursement passager
                    $passengerTransaction = new Transaction($booking->getUserId(), $trip->getTripPrice(), 'cancellation', $tripId);
                    $passengerTransaction->save();

                    // Débit conducteur
                    $driverAmount = $trip->getTripPrice() - self::PLATFORM_FEE;
                    $driverTransaction = new Transaction($trip->getDriverId(), -$driverAmount, 'cancellation', $tripId);
                    $driverTransaction->save();

                    // Débit plateforme
                    $platformTransaction = new Transaction(1, -self::PLATFORM_FEE, 'cancellation', $tripId);
                    $platformTransaction->save();

                    // Envoi de l'email d'annulation au passager
                    $passenger = User::find($booking->getUserId());
                    if ($passenger) {
                        $mailler->sendTripCancellationMail($passenger->getEmail(), $passenger->getFirstname(), $trip);
                    }

                    // Annuler la réservation
                    $booking->cancel();
                }
            }

            // Logique de mise à jour des données du trajet
            if (isset($data['departure_location'])) $trip->setDepartureLocation(htmlspecialchars($data['departure_location'], ENT_QUOTES, 'UTF-8'));
            if (isset($data['arrival_location'])) $trip->setArrivalLocation(htmlspecialchars($data['arrival_location'], ENT_QUOTES, 'UTF-8'));
            if (isset($data['departure_day'])) $trip->setDepartureDay(new DateTime($data['departure_day']));
            if (isset($data['arrival_day'])) $trip->setArrivalDay(new DateTime($data['arrival_day']));
            if (isset($data['departure_time'])) $trip->setDepartureTime(new DateTime($data['departure_time']));
            if (isset($data['arrival_time'])) $trip->setArrivalTime(new DateTime($data['arrival_time']));
            if (isset($data['trip_price'])) $trip->setTripPrice((int)$data['trip_price']);
            if (isset($data['seating'])) $trip->setSeating((int)$data['seating']);
            if (isset($data['animal_pref'])) $trip->setAnimalPref((bool)$data['animal_pref']);
            if (isset($data['smoking_pref'])) $trip->setSmokingPref((bool)$data['smoking_pref']);
            if (isset($data['vehicle_id'])) $trip->setVehicleId((int)$data['vehicle_id']);

            // Recalculer la nature du trajet si le véhicule change
            if (isset($data['vehicle_id'])) {
                $fuelType = Vehicle::getFuelTypeById((int)$data['vehicle_id']);
                if ($fuelType) {
                    $trip->setTripNature(in_array(strtolower($fuelType), ['electric', 'hybrid']) ? 'ecologic' : 'standard');
                }
            }

            // Le trajet repasse toujours en 'pending' après modification
            $trip->setStatus('pending');

            if ($trip->save()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet modifié avec succès. Les réservations existantes ont été annulées et remboursées. Le trajet est en attente de re-validation."]);
            } else {
                throw new Exception("Erreur lors de la modification du trajet.", 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Suppression d'un trajet
     */
    public function deleteTrip(): void
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

            // Rembourser les passagers et envoyer email avant de supprimer
            $bookings = Booking::findByTripId($tripId);
            if (!empty($bookings)) {
                $mailler = new Mailler();
                foreach ($bookings as $booking) {
                    // Remboursement passager
                    $passengerTransaction = new Transaction($booking->getUserId(), $trip->getTripPrice(), 'cancellation', $tripId);
                    $passengerTransaction->save();

                    // Débit conducteur
                    $driverAmount = $trip->getTripPrice() - self::PLATFORM_FEE;
                    $driverTransaction = new Transaction($trip->getDriverId(), -$driverAmount, 'cancellation', $tripId);
                    $driverTransaction->save();

                    // Débit plateforme
                    $platformTransaction = new Transaction(1, -self::PLATFORM_FEE, 'cancellation', $tripId);
                    $platformTransaction->save();

                    // Envoi de l'email d'annulation au passager
                    $passenger = User::find($booking->getUserId());
                    if ($passenger) {
                        $mailler->sendTripCancellationMail($passenger->getEmail(), $passenger->getFirstname(), $trip);
                    }

                    // Annuler la réservation
                    $booking->cancel();
                }
            }

            if ($trip->delete()) {
                http_response_code(200);
                echo json_encode(["message" => "Trajet supprimé et passagers remboursés avec succès."]);
            } else {
                throw new Exception("Erreur lors de la suppression du trajet.", 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Démarrer un trajet (conducteur)
     */
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

    /**
     * Terminer un trajet (conducteur)
     */
    public function endTrip(): void
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
            
            if ($trip->getStatus() !== 'ongoing') {
                throw new Exception("Le trajet ne peut pas être terminé (statut: " . $trip->getStatus() . ").", 400);
            }

            $trip->setStatus('completed');
            if ($trip->save()) {
                // Envoi d'un email aux passagers
                $bookings = Booking::findByTripId($tripId);
                if (!empty($bookings)) {
                    $mailler = new Mailler();
                    foreach ($bookings as $booking) {
                        $passenger = User::find($booking->getUserId());
                        if ($passenger) {
                            $mailler->sendEndRideMail($passenger->getEmail(), $passenger->getFirstname());
                        }
                    }
                }

                http_response_code(200);
                echo json_encode(["message" => "Trajet terminé avec succès."]);
            } else {
                throw new Exception("Erreur lors de la terminaison du trajet.", 500);
            }
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    /**
     * Recuperer tous les trajets d'un utilisateur
     */
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

    /**
     * Recuperer tous les trajets terminés d'un utilisateur
     */
    public function getUserCompletedTrips(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;
            
            $trips = Trip::getCompletedTripsByUser($userId);
            
            if (empty($trips)) {
                http_response_code(200);
                echo json_encode([]);
                return;
            }

            http_response_code(200);
            echo json_encode($trips);
            
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des trajets terminés de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Recuperer les statistiques de trajets (derniers 7 jours)
     */
    public function getWeeklyTrips(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            
            // Vérification du rôle admin (1)
            if ($decodedToken->data->role !== 1) {
                throw new Exception("Accès non autorisé.", 403);
            }

            $stats = Trip::getTripsCountLast7Days();
            
            http_response_code(200);
            echo json_encode($stats);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des statistiques de trajets: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Recherche de trajets avec ou sans filtres
     */
    public function searchWithFiltersOrNot(): void
    {
        header('Content-Type: application/json');
        try {
            // Récupération des paramètres GET
            $filters = [
                'departure' => $_GET['departure'] ?? null,
                'arrival' => $_GET['arrival'] ?? null,
                'departure_day' => $_GET['departure_day'] ?? null,
                'ecologic' => $_GET['ecologic'] ?? null,
                'max_price' => $_GET['max_price'] ?? null,
                'max_duration' => $_GET['max_duration'] ?? null,
                'min_rating' => $_GET['min_rating'] ?? null,
            ];

            // Recherche initiale
            $trips = Trip::searchTrips($filters);

            // Si aucun résultat et qu'une date était spécifiée, on cherche le prochain trajet disponible
            $alternativeDate = null;
            if (empty($trips) && !empty($filters['departure_day'])) {
                // On enlève la date du filtre pour chercher les prochains trajets
                $filtersWithoutDate = $filters;
                unset($filtersWithoutDate['departure_day']);
                
                // On cherche les trajets futurs (après la date demandée)
                $allFutureTrips = Trip::searchTrips($filtersWithoutDate);
                
                $requestedDate = new DateTime($filters['departure_day']);
                
                foreach ($allFutureTrips as $trip) {
                    if ($trip->getDepartureDay() > $requestedDate) {
                        $alternativeDate = $trip->getDepartureDay()->format('Y-m-d');
                        break; // On a trouvé la date la plus proche
                    }
                }
            }

            $tripsArray = array_map(fn($trip) => $trip->toArray(), $trips);

            http_response_code(200);
            echo json_encode([
                'trips' => $tripsArray,
                'alternative_date' => $alternativeDate
            ]);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la recherche de trajets: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Récupération des détails d'un trajet
     */
    public function getTripDetails(): void
    {
        header('Content-Type: application/json');
        try {
            $tripId = $_GET['trip_id'] ?? null;
            if (!$tripId) {
                throw new Exception("ID du trajet manquant.", 400);
            }

            // Utilise searchTrips pour récupérer le trajet avec les infos du conducteur
            $trips = Trip::searchTrips(['trip_id' => $tripId]);

            if (empty($trips)) {
                throw new Exception("Trajet non trouvé.", 404);
            }
            
            $trip = $trips[0];
            $tripData = $trip->toArray();

            // Récupérer les avis pour le conducteur
            $driverId = $trip->getDriverId();
            $driverReviews = Review::getReviewsForDriver($driverId);
            
            // Ajouter les avis au tableau de données
            $tripData['driver_reviews'] = $driverReviews;
            
            http_response_code(200);
            echo json_encode($tripData);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des détails du trajet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
