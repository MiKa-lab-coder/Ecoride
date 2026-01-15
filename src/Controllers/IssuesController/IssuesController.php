<?php

namespace App\Controllers\IssuesController;

use App\Models\Issues;
use App\Models\Trip;
use App\Models\User;
use App\Models\Booking;
use App\Services\TokenValidator;
use App\Services\Mailler;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class IssuesController
 * Gère les opérations liées aux problèmes signalés par les utilisateurs.
 */

class IssuesController
{
    private $logger;

    public function __construct()
    {
        // Initialisation du logger
        $this->logger = new Logger('IssuesController');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/issues.log', 400));
    }

    /**
     * Créer un litige sur un trajet
     */
    public function startIssue(): void
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            $userId = $decodedToken->data->id;

            // Récupération des données
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides.", 400);
            }

            if (!isset($data['trip_id']) || !isset($data['description'])) {
                throw new Exception("Les champs 'trip_id' et 'description' sont obligatoires.", 400);
            }

            $tripId = (int)$data['trip_id'];
            $description = htmlspecialchars($data['description'], ENT_QUOTES, 'UTF-8');

            // Vérification que l'utilisateur existe
            $user = User::find($userId);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID: $userId");
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Vérification que le trajet existe
            $trip = Trip::findById($tripId);
            if (!$trip) {
                $this->logger->error("Trajet non trouvé pour l'ID: $tripId");
                throw new Exception("Trajet non trouvé.", 404);
            }

            // Vérification que le trajet est terminé
            if ($trip->getStatus() !== 'completed') {
                $this->logger->error("Le trajet ID: $tripId n'est pas terminé.");
                throw new Exception("Vous ne pouvez signaler un litige que sur un trajet terminé.", 400);
            }

            // Vérification de la participation (Conducteur ou Passager)
            $isDriver = ($trip->getDriverId() === $userId);
            $isPassenger = Booking::hasUserBookedTrip($userId, $tripId);

            if (!$isDriver && !$isPassenger) {
                $this->logger->error("L'utilisateur ID: $userId n'a pas participé au trajet ID: $tripId.");
                throw new Exception("Vous n'avez pas participé à ce trajet.", 403);
            }

            // Création du litige
            $issue = new Issues(
                'open',
                new \DateTime(),
                $description,
                $userId,
                $tripId
            );

            if ($issue->save()) {
                $this->logger->info("Problème créé avec succès pour l'utilisateur ID: $userId et le trajet ID: $tripId.");
                
                // Envoi d'un email automatique aux modérateurs (simulation)
                $mailler = new Mailler();
                $mailler->sendAutoReportMail($user->getUsername(), (string)$user->getUserId());
                
                http_response_code(201);
                echo json_encode(['message' => 'Litige signalé avec succès.']);
            } else {
                throw new Exception("Erreur lors de l'enregistrement du litige.", 500);
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la création du problème: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cloture d'un litige pour un trajet (pour les admins/modérateurs)
     */
    public function closeIssue(): void
    {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            // Récupération des données depuis le body
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['issue_id'])) {
                 throw new Exception("ID du litige manquant.", 400);
            }
            $issueId = (int)$data['issue_id'];

            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification rôle (admin=1, modérateur=2)
            $userRole = (int)$decodedToken->data->role;
            if (!($userRole === 1 || $userRole === 2)) {
                throw new Exception("Accès refusé.", 403);
            }

            $issue = Issues::findById($issueId);
            if (!$issue) {
                throw new Exception("Litige non trouvé.", 404);
            }

            if ($issue->getStatus() === 'resolved') {
                throw new Exception("Ce litige est déjà résolu.", 400);
            }

            $issue->setStatus('resolved');
            if ($issue->save()) {
                $this->logger->info("Problème ID: $issueId résolu par l'utilisateur ID: {$decodedToken->data->id}.");
                http_response_code(200);
                echo json_encode(['message' => 'Litige clos avec succès.']);
            } else {
                throw new Exception("Erreur lors de la clôture du litige.", 500);
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la résolution du problème: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Récuperation des litiges en cours
     */
    public function viewIssues(): void
    {
        header('Content-Type: application/json');
        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role;
            if (!($userRole === 1 || $userRole === 2)) {
                throw new Exception("Accès refusé.", 403);
            }

            $issues = Issues::getAllIssues();
            $issuesArray = [];
            
            foreach ($issues as $issue) {
                $issuesArray[] = [
                    'id' => (string)$issue->getIssueId(),
                    'status' => $issue->getStatus(),
                    'created_at' => $issue->getDateOpen()->format('Y-m-d H:i:s'),
                    'description' => $issue->getDescription(),
                    'user_id' => (string)$issue->getUserId(),
                    'trip_id' => (string)$issue->getTripId(),
                    'plaintiff_username' => $issue->getPlaintiffUsername(),
                    'plaintiff_email' => $issue->getPlaintiffEmail(),
                    'driver_username' => $issue->getDriverUsername(),
                    'driver_email' => $issue->getDriverEmail(),
                    'trip_departure' => $issue->getTripDeparture(),
                    'trip_arrival' => $issue->getTripArrival(),
                    'trip_date' => $issue->getTripDate(),
                ];
            }
            
            http_response_code(200);
            echo json_encode($issuesArray);

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des problèmes: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
