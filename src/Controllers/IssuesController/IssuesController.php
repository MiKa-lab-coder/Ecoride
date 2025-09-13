<?php

namespace App\Controllers\IssuesController;

use App\Models\Issues;
use App\Models\Trip;
use App\Models\User;
use App\Models\Booking;
use App\Models\Review;
use App\services\TokenValidator;
use App\Services\Mailler;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class IssuesController
 * Gère les opérations liées aux problèmes signalés par les utilisateurs.
 * Pour la creation d'une issue, on doit verifier que l'utilisateur a bien fait le trajet, et que le trajet est terminé.
 * On recupere le commentaire de l'utilisateur sur MongoDB.
 */

class IssuesController
{
    private $logger;

    public function __construct()
    {
        // Initialisation du logger
        $this->logger = new Logger('IssuesController');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
    }

    // Méthode pour créer une nouvelle issue (pour les utilisateurs)
    public function startIssue(array $data): array
    {
        header('Content-Type: application/json');
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification que l'utilisateur existe
            $user = User::find($decodedToken->data->id);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID:$decodedToken->data->id");
                throw new Exception("User not found", 404);
            }

            // Vérification que le trajet existe
            $trip = Trip::findById($data['trip_id']);
            if (!$trip) {
                $this->logger->error("Trajet non trouvé pour l'ID:{$data['trip_id']}");
                throw new Exception("Trip not found", 404);
            }
            // Vérification que le trajet est terminé
            if ($trip->getStatus() !== 'completed') {
                $this->logger->error("Le trajet ID:{$data['trip_id']} n'est pas terminé.");
                throw new Exception("Trip is not completed", 400);
            }
            // Récupération des réservations de l'utilisateur pour le trajet donné
            $bookings = Booking::getUserBookings($user->getUserId());

            // On parcourt les réservations pour vérifier si l'utilisateur a bien fait le trajet
            $userHasParticipated = false;
            foreach ($bookings as $booking) {
                if ($booking->getTripId() === $trip->getTripId()) {
                    $userHasParticipated = true;
                    break;
                }
            }
            if (!$userHasParticipated) {
                $this->logger->error("L'utilisateur ID:{$user->getUserId()} n'a pas participé au trajet 
                ID:{$trip->getTripId()}.");
                throw new Exception("User did not participate in the trip", 400);
            }
            // on récupère le commentaire de l'utilisateur
            $review = new Review();
            $comment = $review->getCommentById($data['comment_id']);
            if (!$comment) {
                $this->logger->error("Commentaire non trouvé pour l'utilisateur ID:{$user->getUserId()} 
                et le trajet ID:{$trip->getTripId()}.");
                throw new Exception("Comment not found", 404);
            }


            // Si tout est bon, on crée le litige
            $issue = new Issues(
                'open',
                new \DateTime(),
                $comment,
                $user->getUserId(),
                $trip->getTripId()
            );
            if ($issue->save()) {
                $this->logger->info("Problème créé avec succès pour l'utilisateur ID:{$user->getUserId()} 
                et le trajet ID:{$trip->getTripId()}.");
                // Envoi d'un email automatique aux modérateurs
                $mailler = new Mailler();
                $mailler->sendAutoReportMail($user->getUsername(), (string)$user->getUserId());
                http_response_code(201);
                return ['message' => 'Issue created successfully'];
            } else {
                $this->logger->error("Erreur lors de la création du problème pour l'utilisateur ID:{$user->getUserId()} 
                et le trajet ID:{$trip->getTripId()}.");
                throw new Exception("Failed to create issue", 500);
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la création du problème: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            return ['error' => $e->getMessage() ?: 'Internal Server Error'];
        }
    }
    // Méthode pour clore une issue (pour les admins/modérateurs)
    // On ne clos pas vraiment l'issue, on change juste son statut en 'resolved'
    public function closeIssue(array $data): array
    {
        header('Content-Type: application/json');
        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            // Vérification que l'utilisateur est admin ou modérateur
            if ($decodedToken->data->role !== 'admin' && $decodedToken->data->role !== 'moderator') {
                $this->logger->error("Accès refusé pour l'utilisateur ID:{$decodedToken->data->id} avec le rôle:
                {$decodedToken->data->role}");
                throw new Exception("Access denied", 403);
            }
            // Vérification que l'issue existe
            $issue = Issues::findById($data['issue_id']);
            if (!$issue) {
                $this->logger->error("Problème non trouvé pour l'ID:{$data['issue_id']}");
                throw new Exception("Issue not found", 404);
            }
            // On s'assure que l'issue n'est pas déjà résolue
            if ($issue->getStatus() === 'resolved') {
                $this->logger->error("Le problème ID:{$data['issue_id']} est déjà résolu.");
                throw new Exception("Issue is already resolved", 400);
            } else {
                $issue->setStatus('resolved');
                if ($issue->save()) {
                    $this->logger->info("Problème ID:{$data['issue_id']} résolu avec succès par l'utilisateur 
                ID:{$decodedToken->data->id}.");
                    http_response_code(200);
                    return ['message' => 'Issue resolved successfully'];
                } else {
                    $this->logger->error("Erreur lors de la résolution du problème ID:{$data['issue_id']} par l'utilisateur 
                ID:{$decodedToken->data->id}.");
                    throw new Exception("Failed to resolve issue", 500);
                }
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la résolution du problème: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            return ['error' => $e->getMessage() ?: 'Internal Server Error'];
        }
    }
}