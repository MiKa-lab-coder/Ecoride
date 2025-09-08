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
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
    }

    private function isAdmin(int $roleId): bool
    {
        return $roleId === 1;
    }

    private function isModeratorOrAdmin(int $roleId): bool
    {
        return $roleId === 1 || $roleId === 2;
    }

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
            $userRole = (int)$decodedToken->data->role_id;
            if (!$this->isModeratorOrAdmin($userRole)) {
                $this->logger->warning("Unauthorized access attempt to moderator section by user ID: {$decodedToken->data->id}");
                throw new Exception("Accès non autorisé.", 403);
            }

            // Récupération des annonces de voyage en attente avec la méthode statique findByStatus
            $pendingTrips = Trip::findByStatus('pending');

            // Envoi de la réponse en cas de succès
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Annonces de voyage en attente récupérées avec succès.",
                "data" => $pendingTrips
            ]);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Error while fetching pending trips: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // Méthode pour valider une annonce
    public function approuveTrips(): void
    {


        // Configuration de l'en-tête de la réponse
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            // Vérification de l'autorisation (seuls les modérateurs et admins peuvent accéder à cette route)
            $userRole = (int)$decodedToken->data->role_id;
            if (!$this->isModeratorOrAdmin($userRole)) {
                $this->logger->warning("Unauthorized access attempt to moderator section by user ID: {$decodedToken->data->id_user}");
                throw new Exception("Accès non autorisé.", 403);
            }
            // recherche de l'annonce par son ID
            $trip = Trip::findById();
            if (!$trip) {
                throw new Exception("Annonce non trouvée.", 404);
            }

            // Confirmation du statut "pending"
            if ($trip->getStatus() !== 'pending') {
                throw new Exception("L'annonce n'est pas en attente de validation.", 400);
            }

            //mise à jour du statut de l'annonce
            $trip->setStatus('approved');

            // Sauvegarde de l'annonce mise à jour
            if (!$trip->save()) {
                throw new Exception("Erreur lors de la validation de l'annonce.", 500);
            }
            // Réponse en cas de succès
            $this->logger->info("Annonce approuvée par l'utilisateur ID: {$decodedToken->data->id_user}");
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Annonce validée avec succès.",
                "data" => $trip
            ]);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la validation: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
            // fin de la validation
        }
    }

    // Methode pour rejeter une annonce
    public function rejectTrips(): void
    {
        // Configuration de l'en-tête de la réponse
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);
            // Vérification de l'autorisation (seuls les modérateurs et admins peuvent accéder à cette route)
            $userRole = (int)$decodedToken->data->role_id;
            if (!$this->isModeratorOrAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autorisé à la suppression de l'annonce
                par l'utilisateur ID:: {$decodedToken->data->user_id}");
                throw new Exception("Accès non autorisé.", 403);
            }
            // recherche de l'annonce par son ID
            $trip = Trip::findById();
            if (!$trip) {
                throw new Exception("Annonce non trouvée.", 404);
            }

            // Confirmation du statut "pending"
            if ($trip->getStatus() !== 'pending') {
                throw new Exception("L'annonce n'est pas en attente de validation.", 400);
            }

            //Suppression de l'annonce
            if (!$trip->delete()) {
                throw new Exception("Erreur lors du rejet de l'annonce.", 500);
            }

            // Réponse en cas de succès
            $this->logger->info("Annonce rejetée par l'utilisateur ID: {$decodedToken->data->id_user}");
            http_response_code(200);
            echo json_encode([
                "success" => true,
                "message" => "Annonce rejetée avec succès.",
                "data" => $trip
            ]);
            exit;

        } catch (Exception $e) {
            $this->logger->error("Erreur pour la tentative de rejet: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
            // fin du rejet
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

            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation (seuls les admins peuvent accéder à cette route)
            $userRole = (int)$decodedToken->data->role_id;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autorisé par l'user: {$decodedToken->data->id_user}");
                throw new Exception("Accès non autorisé.", 403);
            }

            //Vérifier si la requête est en POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            //Echappement des données pour éviter les failles XSS
            $data = json_decode(file_get_contents('php://input'), true);
            // Échappement des données pour éviter les failles XSS
            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $role_id = isset($data['role_id']) ? (int)$data['role_id'] : 3; // Par défaut, role_id 3 pour utilisateur

            // Vérification de la présence des champs requis
            if (empty($username) || empty($password)) {
                throw new Exception("Champs requis manquants.", 400);
            }
            // Validation des données
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
            // Vérification de l'unicité du nom d'utilisateur et de l'email
            if (User::existUsername($username)) {
                throw new Exception("Nom d'utilisateur déjà pris.", 409);
            }
            if (User::existsEmail($email)) {
                throw new Exception("Email déjà utilisé.", 409);
            }

            /**
             * Création du nouvel utilisateur moderateur ou utilisateur
             * Hashage du mot de passe inclus dans setPassword() de User
             * On récupère le role_id depuis le formulaire via le champ select
             */

            $user = new User(
                'nom',
                'prenom',
                new DateTime(),
                $username,
                'uploads/default.png',
                $email,
                $password,
                20,
                0,
                'active',
                $role_id
            );
            if (!$user->save()) {
                throw new Exception("Erreur lors de la création du compte.", 500);
            }
    }catch (Exception $e) {
            $this->logger->error("Erreur lors de la creation du compte: " . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["success" => false, "message" => $e->getMessage()]);
            exit;
        }
    }

    // Méthode pour suspendre un utilisateur
    public function suspendUser(string $username, string $email): void
    {
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation (seuls les admins peuvent accéder à cette route)
            $userRole = (int)$decodedToken->data->role_id;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autorisé par l'user: {$decodedToken->data->id_user}");
                throw new Exception("Accès non autorisé.", 403);
            }

            // Vérification de la méthode de la requête
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }
            $data = json_decode(file_get_contents('php://input'), true);

            // Échappement des données pour éviter les failles XSS
            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');

            // Vérification de la présence des champs requis
            if (empty($username) && empty($email)) {
                throw new Exception("Champs requis manquants.", 400);
            }

            // recherche de l'utilisateur par son username ou son email
            $user = User::findByUsername($username) ?? User::findByEmail($email);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Vérification du statut de l'utilisateur
            if ($user->getAccountStatus() === 'suspended') {
                throw new Exception("L'utilisateur est déjà suspendu.", 400);
            }

            // Suspension de l'utilisateur
            $user->setAccountStatus('suspended');
            if (!$user->save()) {
                throw new Exception("Erreur lors de la suspension de l'utilisateur.", 500);
            }
            // Réponse en cas de succès
            $this->logger->info("Utilisateur ID {$user->getIdUser()} suspendu par l'utilisateur ID: 
            {$decodedToken->data->id_user}");
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
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            $userRole = (int)$decodedToken->data->role_id;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autorisé par l'user: {$decodedToken->data->id_user}");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vérification de la méthode de la requête
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            // Échappement des données pour éviter les failles XSS
            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');

            // Vérification de la présence des champs requis
            if (empty($username) && empty($email)) {
                throw new Exception("Champs requis manquants.", 400);
            }
            // recherche de l'utilisateur par son username ou son email
            $user = User::findByUsername($username) ?? User::findByEmail($email);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Vérification du statut de l'utilisateur
            if ($user->getAccountStatus() === 'active') {
                throw new Exception("L'utilisateur est déjà actif.", 400);
            }
            // Réactivation de l'utilisateur
            $user->setAccountStatus('active');

            // Sauvegarde de l'utilisateur réactivé
            if (!$user->save()) {
                throw new Exception("Erreur lors de la réactivation de l'utilisateur.", 500);
            }

            $this->logger->info("Utilisateur ID {$user->getIdUser()} réactivé par l'utilisateur ID:
             {$decodedToken->data->id_user}");
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
    public function changeUserRole(string $username, string $email, string $role_id): void
    {
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // Vérification de l'autorisation (seuls les admins peuvent accéder à cette route)
            $userRole = (int)$decodedToken->data->role_id;
            if (!$this->isAdmin($userRole)) {
                $this->logger->warning("Tentative d'accès non autorisé par l'user: {$decodedToken->data->id_user}");
                throw new Exception("Accès non autorisé.", 403);
            }
            // Vérification de la méthode de la requête
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Méthode non autorisée.", 405);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            // Échappement des données pour éviter les failles XSS
            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');
            $role_id = (int)($data['role_id'] ?? 0);


            // Vérification de la présence des champs requis
            if (empty($username) && empty($email)) {
                throw new Exception("Champs requis manquants.", 400);
            }
            // Validation du rôle (seuls les rôles 2 et 3 peuvent être attribués)
            if (!in_array($role_id, [2, 3])) {
                throw new Exception("Rôle invalide.", 400);
            }

            // recherche de l'utilisateur par son username ou son email
            $user = User::findByUsername($username) ?? User::findByEmail($email);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }
            // Récupération du rôle actuel de l'utilisateur
            $currentRole = $user->getRoleId();

            // Modification du rôle de l'utilisateur
            $user->setRoleId((int)$role_id);
            if (!$user->save()) {
                throw new Exception("Erreur lors de la modification du rôle de l'utilisateur.", 500);
            }
            // Réponse en cas de succès
            $this->logger->info("Rôle de l'utilisateur ID {$user->getIdUser()} modifié par l'utilisateur ID:
             {$decodedToken->data->id_user}");
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