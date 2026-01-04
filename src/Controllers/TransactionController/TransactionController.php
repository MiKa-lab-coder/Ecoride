<?php

namespace App\Controllers\TransactionController;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Trip;
use App\Services\Validator;
use App\Services\TokenValidator;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class TransactionController
 * Gère les opérations liées aux transactions de crédit sur la plateforme (payment, frais, remboursement, stats).
 * Elles seront toutes automatisées et déclenchées par des événements (création de trajet, réservation, annulation, etc.)
 * Utilise le modèle Transaction pour interagir avec la base de données.
 * Utilise le modèle User pour vérifier l'existence des utilisateurs.
 * Utilise le modèle Trip pour vérifier l'existence des trajets.
 * Utilise le service Validator pour valider les données d'entrée.
 * Utilise le service TokenValidator pour valider les tokens JWT.
 */
class TransactionController
{
    // Frais de service de la plateforme (2 Crédits par transaction)
    private const PLATFORM_FEE = 2;
    private Logger $logger;

    public function __construct()
    {
        // Initialisation du logger
        $this->logger = new Logger('TransactionController');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/app.log', 100));
    }

    /**
     * Méthode pour payer un trajet
     */
    public function payTrip($userId, $amount, $tripRef): false|string
    {
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation
            if ((int)$decodedToken->data->id !== $userId) {
                $this->logger->warning("Tentative d'accès non autorisé au profil de l'utilisateur ID: $userId");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vrification de l'existence de l'utilisateur
            $user = User::find($userId);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID: $userId");
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Vérification de l'existence du trajet
            $trip = Trip::findById($tripRef);
            if (!$trip) {
                $this->logger->error("Trajet non trouvé pour la référence: $tripRef");
                throw new Exception("Trajet non trouvé.", 404);
            }
            // Validation du montant (doit être un entier positif)
            if ($amount <= 0) {
                $this->logger->error("Montant de transaction invalide: $amount pour l'utilisateur ID: $userId");
                throw new Exception("Montant de transaction invalide.", 400);
            }
            // Création de la transaction de paiement (utilisateur : débit du montant + frais de plateforme)
            $userTransaction = new Transaction(
                user_id: $userId,
                amount: -$amount, // Débit du montant du trajet
                transaction_type: 'payment',
                reference: $tripRef
            );
            $userTransaction->save();

            // Création de la transaction de rémunération du chauffeur (utilisateur: crédit du montant - frais de plateforme)
            // Montant perçu par le chauffeur = montant du trajet - frais de plateforme
            $driverAmount = $amount - self::PLATFORM_FEE;

            $driverId = $trip->getDriverId();
            $diverTransaction = new Transaction(
                user_id: $driverId, // ID du chauffeur
                amount: $driverAmount, // Crédit du montant du trajet - frais de plateforme
                transaction_type: 'payment',
                reference: $tripRef
            );
            $diverTransaction->save();

            // Création de la transaction de frais de plateforme (utilisateur: crédit des frais de plateforme)
            $platformTransaction = new Transaction(
                user_id: 1, // ID de l'utilisateur "Admin"
                amount: self::PLATFORM_FEE, // Crédit des frais de plateforme
                transaction_type: 'service_fee',
                reference: $tripRef
            );
            $platformTransaction->save();

            // Retourner les nouveaux soldes des utilisateurs
            $userBalace = Transaction::getUserBalance($userId);
            $driverBalance = Transaction::getUserBalance($driverId);
            $platformBalance = Transaction::getUserBalance(1); // Solde de l'utilisateur "Admin"

            return json_encode([
                'message' => 'Paiement du trajet effectué avec succès.',
                'user_balance' => $userBalace,
                'driver_balance' => $driverBalance,
                'platform_balance' => $platformBalance
            ]);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors du paiement du trajet ID:
             $tripRef par l'utilisateur ID: $userId - " . $e->getMessage());
            http_response_code(500);
            return json_encode(['message' => 'Erreur de paiement.', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Rembourser un trajet (automatiquement)
     */
    public function payBackTrip($userId, $amount, $tripRef): false|string
    {
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation
            if ((int)$decodedToken->data->id !== $userId) {
                $this->logger->warning("Tentative d'accès non autorisé au profil de l'utilisateur ID: $userId");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vrification de l'existence de l'utilisateur
            $user = User::find($userId);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID: $userId");
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Vérification de l'existence du trajet
            $trip = Trip::findById($tripRef);// tripRef est l'id du trajet
            if (!$trip) {
                $this->logger->error("Trajet non trouvé pour la référence: $tripRef");
                throw new Exception("Trajet non trouvé.", 404);
            }
            // Validation du montant (doit être un entier positif)
            if ($amount <= 0) {
                $this->logger->error("Montant de transaction invalide: $amount pour l'utilisateur ID: $userId");
                throw new Exception("Montant de transaction invalide.", 400);
            }
            // Recuperation de l'ID du chauffeur
            $driverId = $trip->getDriverId();
            if (!$driverId) {
                $this->logger->error("Chauffeur non trouvé pour le trajet ID: $tripRef");
                throw new Exception("Chauffeur non trouvé pour ce trajet.", 404);
            }
            // Montant à rembourser au passager = montant du trajet - frais de plateforme
            $payBackAmount = $amount - self::PLATFORM_FEE;

            // Création de la transaction de remboursement (utilisateur : credit du montant total)
            $userTransaction = new Transaction(
                user_id: $userId,
                amount: $payBackAmount, // Crédit total du montant du trajet
                transaction_type: 'cancellation',
                reference: $tripRef
            );
            $userTransaction->save();

            // Création de la transaction de débit du chauffeur (Chauffeur : débit du montant - frais de plateforme)
            $driverTransaction = new Transaction(
                user_id: $driverId, // ID du chauffeur
                amount: -$payBackAmount, // Débit du montant du trajet - frais de plateforme
                transaction_type: 'cancellation',
                reference: $tripRef
            );
            $driverTransaction->save();
            // Création de la transaction de frais de plateforme (Admin : débit des frais de plateforme)
            $platformTransaction = new Transaction(
                user_id: 1, // ID de l'utilisateur "Admin"
                amount: -self::PLATFORM_FEE, // Débit des frais de plateforme
                transaction_type: 'cancellation',
                reference: $tripRef
            );
            $platformTransaction->save();

            // Retourner les nouveaux soldes des utilisateurs
            $userBalace = Transaction::getUserBalance($userId);
            $driverBalance = Transaction::getUserBalance($driverId);
            $platformBalance = Transaction::getUserBalance(1); // Solde de l'utilisateur "Admin"

            return json_encode([
                'message' => 'Remboursement du trajet effectué avec succès.',
                'user_balance' => $userBalace,
                'driver_balance' => $driverBalance,
                'platform_balance' => $platformBalance
            ]);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors du remboursement du trajet ID:}
             $tripRef par l'utilisateur ID: $userId - " . $e->getMessage());
            http_response_code(500);
            return json_encode(['message' => 'Erreur de remboursement.', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Récupération des statistiques de la plateforme (7 derniers jours)
     */
    public function getPlatformStats(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation (seul l'admin peut accéder aux stats)
            if ((int)$decodedToken->data->role !== 1) {
                $this->logger->warning("Tentative d'accès non autorisé aux statistiques de la plateforme par
                 l'utilisateur ID: " . $decodedToken->data->id);
                throw new Exception("Accès non autorisé.", 403);
            }


            // Calcul de la plage de dates (vue sur 7 jours)
            $endDate = new \DateTime();
            $startDate = (new \DateTime())->sub(new \DateInterval('P7D'));
            
            // Récupération des statistiques de la plateforme
            $dailyStats = Transaction::getPlatformEarningsByDate(
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            // Formatage pour Chart.js
            $labels = [];
            $data = [];
            $earningsByDate = [];

            foreach ($dailyStats as $stat) {
                $earningsByDate[$stat['date']] = (int)$stat['daily_earnings'];
            }

            // Boucle sur les 7 derniers jours pour avoir des données continues
            for ($i = 6; $i >= 0; $i--) {
                $date = (new \DateTime())->modify("-$i days")->format('Y-m-d');
                $labels[] = (new \DateTime($date))->format('d/m');
                $data[] = $earningsByDate[$date] ?? 0;
            }

            http_response_code(200);
            echo json_encode([
                'labels' => $labels,
                'data' => $data
            ]);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération des statistiques de la plateforme - " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['message' => 'Erreur lors de la récupération des statistiques.', 'error' => $e->getMessage()]);
            exit;
        }
    }
    /**
     * Récupération du solde de crédit d'un utilisateur
     */
    public function getUserBalance($userId): false|string
    {
        header('Content-Type: application/json');
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation
            if ((int)$decodedToken->data->id !== $userId) {
                $this->logger->warning("Tentative d'accès non autorisé au profil de l'utilisateur ID: $userId");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vrification de l'existence de l'utilisateur
            $user = User::find($userId);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID: $userId");
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Récupération du solde de crédit de l'utilisateur
            $balance = Transaction::getUserBalance($userId);
            return json_encode([
                'user_id' => $userId,
                'balance' => $balance
            ]);
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récupération du solde de l'utilisateur ID: $userId - " . $e->getMessage());
            http_response_code(500);
            return json_encode(['message' => 'Erreur lors de la récupération du solde.', 'error' => $e->getMessage()]);
        }
    }
}