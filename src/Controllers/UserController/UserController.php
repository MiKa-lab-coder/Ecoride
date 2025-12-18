<?php

namespace App\Controllers\UserController;

use App\Models\User;
use App\Services\TokenValidator;
use App\Services\Validator;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Exception;


/**
 * Class UserController
 * Gère les actions liées aux utilisateurs (hors authentification, inscription et connexion -> voir AuthController)
 * On y trouve la gestion du profil utilisateur, la modification des informations, laisser un commentaire, etc.
 * On y gere uniquement les actions que l'utilisateur peut faire sur son propre compte
 * Pour la sécurité, on gere le XSS dans le controller (htmlspecialchars), le JWT avec TokenValidator,
 * et la validation de format des données avec Validator
 * Les fonctionnalités de moderation (Moderator et admin) seront gérées dans AdminController
 */
class UserController
{
    private Logger $logger;

    public function __construct()
    {
        // Initialisation unique du logger
        $this->logger = new Logger('user_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
    }

    /**
     * Affiche le profil de l'utilisateur connecté à partir de son token JWT.
     * @return void
     */
    public function showMyProfile(): void
    {
        header('Content-Type: application/json');

        try {
            // Récupération du token depuis l'en-tête Authorization
            $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

            // On valide le token JWT et on récupère les données décodées
            $tokenValidator = new TokenValidator();
            $decodedToken = $tokenValidator->validateToken($token);

            // On récupère l'ID de l'utilisateur depuis le token
            $userId = $decodedToken->data->id;

            // Récupération des informations de l'utilisateur
            $user = User::find($userId);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID: $userId (depuis token)");
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Envoi de la réponse en cas de succès
            $this->logger->info("Affichage du profil pour ID: $userId");
            http_response_code(200);
            echo json_encode([
                'id' => $user->getUserId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'firstname' => $user->getFirstname(),
                'birthdate' => $user->getBirthDate()->format('d-m-Y'),
                'role_id' => $user->getRoleId(),
                'photo' => $user->getPhoto(),
                'total_trips' => $user->getTotalTrips(),
                'driver_rating' => $user->getDriverRating(),
                'credit' => $user->getCredit(),
            ]);
            exit;

        } catch (Exception $e) {
            $code_status = $e->getCode() > 100 ? $e->getCode() : 500;
            $this->logger->error("Erreur lors de la récupération du profil via token - " . $e->getMessage());
            http_response_code($code_status);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Met à jour le profil de l'utilisateur connecté à partir de son ID.
     * @param int $userId L'ID de l'utilisateur à mettre à jour.
     * @return void
     */
    public function updateMyProfile(int $userId): void
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

            // Récupération des données du corps de la requête
            $requestData = json_decode(file_get_contents('php://input'), true);
            if ($requestData === null) {
                throw new Exception("Données invalides.", 400);
            }

            // Récupération de l'utilisateur
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Validation et mise à jour des données
            $validator = new Validator();
            if (isset($requestData['username'])) {
                $username = htmlspecialchars(trim($requestData['username']));
                if (!$validator->validateUsername($username)) {
                    throw new Exception("Nom d'utilisateur invalide.", 400);
                }
                $user->setUsername($username);
            }
            if (isset($requestData['email'])) {
                $email = filter_var(trim($requestData['email']), FILTER_SANITIZE_EMAIL);
                if (!$validator->validateEmail($email)) {
                    throw new Exception("Email invalide.", 400);
                }
                $user->setEmail($email);
            }
            if (isset($requestData['name'])) {
                $name = htmlspecialchars(trim($requestData['name']));
                if (!$validator->validateName($name)) {
                    throw new Exception("Nom invalide.", 400);
                }
                $user->setName($name);
            }
            if (isset($requestData['firstname'])) {
                $firstname = htmlspecialchars(trim($requestData['firstname']));
                if (!$validator->validateFirstname($firstname)) {
                    throw new Exception("Prénom invalide.", 400);
                }
                $user->setFirstname($firstname);
            }

            // Sauvegarde de l'utilisateur mis à jour dans la base de données
            if (!$user->save()) {
                throw new Exception("Erreur lors de la mise à jour du profil.", 500);
            }

            // Envoi de la réponse en cas de succès
            $this->logger->info("Mise à jour du profil pour ID: $userId");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès.']);
            exit;

        } catch (Exception $e) {
            $code_status = $e->getCode() > 100 ? $e->getCode() : 500;
            $this->logger->error("Erreur lors de la mise à jour du profil pour l'ID: $userId - " . $e->getMessage());
            throw new exception("erreur",$e->getMessage(), $code_status);
        }
    }
    public function updateMyPhoto(int $userId): void
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

            // Récupération de l'utilisateur
            $user = User::find($userId);
            if (!$user) {
                throw new Exception("Utilisateur non trouvé.", 404);
            }

            // Validation de la photo avec Validator
            $validator = new Validator();
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Erreur lors du téléchargement de la photo.", 400);
            }
            $photo = $_FILES['photo'];
            $photoErrors = $validator->validatePhoto($photo);
            if (!empty($photoErrors)) {
                throw new Exception(implode(" ", $photoErrors), 400);
            }

            // Enregistrement de la photo
            $uploadDir = __DIR__ . '/../../public/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $photoName = uniqid() . '_' . basename($photo['name']);
            $uploadFile = $uploadDir . $photoName;
            if (!move_uploaded_file($photo['tmp_name'], $uploadFile)) {
                throw new Exception("Échec du téléchargement de la photo de profil.", 500);
            }

            // Mise à jour du chemin de la photo dans l'utilisateur
            $photoPath = '/uploads/' . $photoName;
            $user->setPhoto($photoPath);

            // Sauvegarde de l'utilisateur mis à jour dans la base de données
            if (!$user->save()) {
                throw new Exception("Erreur lors de la mise à jour de la photo de profil.", 500);
            }

            // Envoi de la réponse en cas de succès
            $this->logger->info("Mise à jour de la photo de profil pour ID: $userId");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Photo de profil mise à jour avec succès.', 'newProfilePicUrl' => $photoPath]);
            exit;

        } catch (Exception $e) {
            $code_status = $e->getCode() > 100 ? $e->getCode() : 500;
            $this->logger->error("Erreur lors de la mise à jour de la photo pour l'ID: $userId - " . $e->getMessage());
            http_response_code($code_status);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    // Recuperer la photo d'un utilisateur pour l'afficher
    public function showMyPhoto(int $userId): void
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

            // Récupération des informations de l'utilisateur
            $user = User::find($userId);
            if (!$user) {
                $this->logger->error("Utilisateur non trouvé pour l'ID: $userId");
                throw new Exception("Ressource non trouvée.", 404);
            }

            // Envoi de la réponse en cas de succès
            $this->logger->info("Affichage de la photo de profil pour ID: $userId");
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Photo de profil récupérée avec succès.',
                'profilePictureUrl' => $user->getPhoto(),
            ]);
            exit;
        } catch (Exception $e) {
            $code_status = $e->getCode() > 100 ? $e->getCode() : 500;
            $this->logger->error("Erreur lors de la récupération de la photo pour l'ID: $userId - " . $e->getMessage());
            http_response_code($code_status);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}