<?php

namespace App\Controllers\AdminController;

use App\Models\Trip;
use App\Models\User;
use App\Services\Validator;
use App\Services\TokenValidator;
use DateTime;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;

/**
 * Class AdminController
 * Gère les actions liées à l'administration du site, telles que la gestion des utilisateurs,
 * la modération des contenus, et d'autres fonctionnalités réservées aux administrateurs.
 * On s'assure bien de verifier les permissions d'admin et de modérateur avant chaque action.
 */
class AdminController
{
    private Logger $logger;

    public function __construct()
    {
        // Initialisation unique du logger
        $this->logger = new Logger('admin_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/app.log', 100));
    }

    private function isAdmin(int $roleId): bool
    {
        return $roleId === 1;
    }

    private function isModeratorOrAdmin(int $roleId): bool
    {
        return $roleId === 1 || $roleId === 2;
    }

    // Méthode pour récupérer les annonces de voyage en attente de validation
    public function getPendingTrips(): void
    {
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation (seuls les modérateurs et admins peuvent accéder à cette route)
            $userRole = (int)$decodedToken->data->role;
            if (!$this->isModeratorOrAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autoriser par l'utilisateur ID: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            // Récupération des annonces de voyage en attente avec la méthode statique findByStatus
            $pendingTripsObjects = Trip::findByStatus('pending');
            
            // Transformation des objets Trip en tableaux
            $pendingTripsArray = [];
            foreach ($pendingTripsObjects as $trip) {
                $pendingTripsArray[] = $trip->toArray();
            }

            // Envoi de la réponse en cas de succès
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Annonces de voyage en attente récupérées avec succès.",
                "data" => $pendingTripsArray
            ]);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la récuperation des trajet en attentes: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // Méthode pour valider une annonce
    public function approuveTrips(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role;
            if (!$this->isModeratorOrAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autoriser par l'utilisateur ID: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['trip_id'])) {
                throw new Exception("L'identifiant du voyage est manquant.", 400);
            }
            $tripId = (int)$data['trip_id'];

            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Annonce non trouvée.", 404);
            }

            if ($trip->getStatus() !== 'pending') {
                throw new Exception("L'annonce n'est pas en attente de validation.", 400);
            }

            $trip->setStatus('approved');

            if (!$trip->save()) {
                throw new Exception("Erreur lors de la validation de l'annonce.", 500);
            }

            $this->logger->info("Annonce {$tripId} approuvée par l'utilisateur ID: {$decodedToken->data->id}");
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Annonce validée avec succès.",
                "data" => $trip->toArray()
            ]);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la validation: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // Methode pour rejeter une annonce
    public function rejectTrips(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role;
            if (!$this->isModeratorOrAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autoriser par l'utilisateur ID: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['trip_id'])) {
                throw new Exception("L'identifiant du voyage est manquant.", 400);
            }
            $tripId = (int)$data['trip_id'];

            $trip = Trip::findById($tripId);
            if (!$trip) {
                throw new Exception("Annonce non trouvée.", 404);
            }

            if ($trip->getStatus() !== 'pending') {
                throw new Exception("L'annonce n'est pas en attente de validation.", 400);
            }

            if (!$trip->delete()) {
                throw new Exception("Erreur lors du rejet de l'annonce.", 500);
            }

            $this->logger->info("Annonce {$tripId} rejetée par l'utilisateur ID: {$decodedToken->data->id}");
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Annonce rejetée avec succès."
            ]);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur pour la tentative de rejet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // admin uniquement : gestion des utilisateurs (creation, suspension, modification des rôles, recuperation des stats...)

    /**
     * Pour la gestion des comptes, on utilise un seul et unique formulaire, on s'assure d'enlever required en front
     * Pour la creation du compte, et on vérifie en back que les champs requis sont bien présents
     * Les admins peuvent créer des comptes moderateurs ou utilisateurs
     * Les modérateurs ne peuvent pas créer de comptes.
     */

    public function createAccount(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Unauthorized access attempt by user: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides ou manquantes.", 400);
            }

            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $roleId = isset($data['role_id']) ? (int)$data['role_id'] : 3;
            $firstName = htmlspecialchars($data['first_name'] ?? 'nom');
            $lastName = htmlspecialchars($data['last_name'] ?? 'prenom');
            $birthDate = new DateTime($data['birth_date'] ?? '1990-01-01');
            $profilePicture = htmlspecialchars($data['profile_picture'] ?? 'uploads/default.png');
            $rating = (int)($data['rating'] ?? 0);
            $totalTrips = (int)($data['total_trips'] ?? 0);
            $accountStatus = htmlspecialchars($data['account_status'] ?? 'active');

            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception("Champs requis (username, email, password) manquants.", 400);
            }

            $validator = new Validator();
            if (!$validator->validateUsername($username)) {
                throw new Exception("Nom d'utilisateur invalide. Doit contenir 3 à 20 caractères alphanumériques."
                    , 400);
            }
            if (!$validator->validateEmail($email)) {
                throw new Exception("Email invalide.", 400);
            }
            if (!$validator->validatePassword($password)) {
                throw new Exception("Mot de passe invalide. Doit contenir au moins 8 caractères, une majuscule,
                 une minuscule, un chiffre et un caractère spécial.", 400);
            }

            if (User::existUsername($username)) {
                throw new Exception("Nom d'utilisateur déjà pris.", 409);
            }
            if (User::existsEmail($email)) {
                throw new Exception("Email déjà utilisé.", 409);
            }

            $user = new User(
                $firstName,
                $lastName,
                $birthDate,
                $username,
                $profilePicture,
                $email,
                $password,
                $rating,
                $totalTrips,
                $accountStatus,
                $roleId
            );

            if (!$user->save()) {
                throw new Exception("Erreur lors de la création du compte.", 500);
            }

            http_response_code(201);
            echo json_encode(["success" => true, "message" => "Compte créé avec succès.", "user_id" => $user->getUserId()]);
            $this->logger->info("Nouveau compte créé par l'admin: {$decodedToken->data->id}");
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la creation du compte: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // Méthode pour suspendre un utilisateur
    public function suspendUser(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Unauthorized access attempt by user: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides ou manquantes.", 400);
            }
            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');

            if (empty($username) && empty($email)) {
                throw new Exception("Champs requis (username ou email) manquants.", 400);
            }

            $user = User::findByUsername($username) ?? User::findByEmail($email);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            if ($user->getAccountStatus() === 'suspended') {
                throw new Exception("L'utilisateur est déjà suspendu.", 400);
            }

            $user->setAccountStatus('suspended');
            if (!$user->save()) {
                throw new Exception("Erreur lors de la suspension de l'utilisateur.", 500);
            }

            $this->logger->info("Utilisateur {$user->getUserId()} suspendu par l'utilisateur ID: 
            {$decodedToken->data->id}");
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Utilisateur suspendu avec succès.",
            ]);
            exit;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la suspension de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // Méthode pour réactiver un utilisateur
    public function reactivateUser(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Unauthorized access attempt by user: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides ou manquantes.", 400);
            }

            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');

            if (empty($username) && empty($email)) {
                throw new Exception("Champs requis (username ou email) manquants.", 400);
            }

            $user = User::findByUsername($username) ?? User::findByEmail($email);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            if ($user->getAccountStatus() === 'active') {
                throw new Exception("L'utilisateur est déjà actif.", 400);
            }

            $user->setAccountStatus('active');

            if (!$user->save()) {
                throw new Exception("Erreur lors de la réactivation de l'utilisateur.", 500);
            }

            $this->logger->info("Utilisateur {$user->getUserId()} réactivé par l'utilisateur ID: {$decodedToken->data->id}");
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Utilisateur réactivé avec succès.",
            ]);
            exit;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la réactivation de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // Méthode pour modifier utilisateur (role uniquement pour l'instant)
    public function changeUserRole(): void
    {
        header('Content-Type: application/json');

        try {
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Unauthorized access attempt by user: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                throw new Exception("Données invalides ou manquantes.", 400);
            }

            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');
            $roleId = (int)($data['role_id'] ?? 0);

            if (empty($username) && empty($email)) {
                throw new Exception("Champs requis (username ou email) manquants.", 400);
            }

            if (!in_array($roleId, [2, 3])) {
                throw new Exception("Rôle invalide. Les rôles valides sont modérateur (2) et utilisateur (3).", 400);
            }

            $user = User::findByUsername($username) ?? User::findByEmail($email);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            $user->setRoleId($roleId);
            if (!$user->save()) {
                throw new Exception("Erreur lors de la modification du rôle de l'utilisateur.", 500);
            }

            $this->logger->info("Rôle de l'utilisateur {$user->getUserId()} modifié par l'utilisateur ID:
             {$decodedToken->data->id}");
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Rôle de l'utilisateur modifié avec succès.",
            ]);
            exit;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la modification du rôle de l'utilisateur: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }
}
