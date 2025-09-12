<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Validator;
use App\Models\Transaction;
use App\Services\TokenManager;
use DateTime;
use PDOException;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Services\Mailler;

/**
 * Class AuthController
 * Gère les actions liées à l'authentification, l'inscription et la connexion des utilisateurs
 * On y gère le XSS dans le controller (htmlspecialchars), le JWT avec TokenManager,
 * et la validation de format des données avec Validator
 *
 */
class AuthController
{
    public function registration(): void
    {
        $logger = new Logger('register_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', Logger::INFO));

        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(["error" => "Méthode non autorisée."]);
                exit;
            }

            $data = $_POST;
            $photo = $_FILES['photo'] ?? null;
            $validator = new Validator();
            $errors = [];

            $name = htmlspecialchars($data['name'] ?? '');
            $firstname = htmlspecialchars($data['firstname'] ?? '');
            $birthdate = htmlspecialchars($data['birthdate'] ?? '');
            $username = htmlspecialchars($data['username'] ?? '');
            $email = htmlspecialchars($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $confirmPassword = $data['confirmPassword'] ?? '';

            // Validation des champs
            if (!$validator->validateName($name)) {
                $errors['name'] = 'Nom invalide. Utilisez uniquement des lettres et espaces, entre 2 et 50 caractères.';
            }
            if (!$validator->validateFirstname($firstname)) {
                $errors['firstname'] = 'Prénom invalide. Utilisez uniquement des lettres et espaces, entre 2 et 50 caractères.';
            }
            if (!$validator->validateBirthdate($birthdate)) {
                $errors['birthdate'] = 'Date de naissance invalide. Utilisez le format JJ-MM-AAAA.';
            } elseif (!$validator->validateAge($birthdate)) {
                $errors['birthdate'] = 'Vous devez avoir au moins 18 ans pour vous inscrire.';
            }
            if (!$validator->validateUsername($username)) {
                $errors['username'] = 'Nom d\'utilisateur invalide. Utilisez uniquement des caractères alphanumériques, entre 3 et 20 caractères.';
            }
            if (!$validator->validateEmail($email)) {
                $errors['email'] = 'Email invalide.';
            }
            if (!$validator->validatePassword($password)) {
                $errors['password'] = 'Mot de passe invalide. Il doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
            } elseif ($password !== $confirmPassword) {
                $errors['confirmPassword'] = 'Les mots de passe ne correspondent pas.';
            }
            if (User::existsEmail($email)) {
                $errors['email'] = 'Email déjà utilisé.';
            }
            if (User::existUsername($username)) {
                $errors['username'] = 'Nom d\'utilisateur déjà pris.';
            }

            $photo_path = 'uploads/default.png';
            if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                $photoErrors = $validator->validatePhoto($photo);
                if (!empty($photoErrors)) {
                    $errors = array_merge($errors, $photoErrors);
                } else {
                    $uploadDir = __DIR__ . '/../../public/uploads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $photoName = uniqid() . '_' . basename($photo['name']);
                    $uploadFile = $uploadDir . $photoName;
                    if (move_uploaded_file($photo['tmp_name'], $uploadFile)) {
                        $photo_path = '/uploads/' . $photoName;
                    } else {
                        $errors['photo'] = 'Échec du téléchargement de la photo de profil.';
                        $logger->error('Échec du téléchargement de la photo de profil.');
                    }
                }
            }

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(["error" => "Données invalides.", "details" => $errors]);
                exit;
            }

            $birthdateFormatted = DateTime::createFromFormat('d-m-Y', $birthdate);
            $user = new User(
                $name,
                $firstname,
                $birthdateFormatted,
                $username,
                $photo_path,
                $email,
                $password,
                0
            );

            if ($user->save()) {
                $logger->info("Nouvel utilisateur inscrit: $username");

                // on crée une transaction initiale de 20 crédits pour le nouvel utilisateur
                $welcomeCredit = new Transaction(
                    $user->getUserId(),
                    20,
                    'welcome_bonus',
                    0 // pas de référence pour les crédits de bienvenue
                );
                // on enregistre la transaction
                if ($welcomeCredit->save()) {
                    $logger->info("Crédits de bienvenue ajoutés pour l'utilisateur: $username");
                } else {
                    $logger->error("Échec de l'ajout des crédits de bienvenue pour l'utilisateur: $username");
                }
                // On envoie l'e-mail de confirmation
                $mailler = new Mailler();
                try {
                    $mailler->sendComfirmationMail($email, $username);
                    $logger->info("Email de confirmation envoyé à $email");
                } catch (Exception $e) {
                    $logger->error("Erreur lors de l'envoi de l'email de confirmation à $email: " . $e->getMessage());
                }

                http_response_code(201);
                echo json_encode(["message" => "Inscription réussie."]);
                exit;
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Erreur lors de l'inscription."]);
                exit;
            }

        } catch (Exception $e) {
            $logger->error('Erreur inscription: ' . $e->getMessage());
            http_response_code($e->getCode() ?: 500);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }

    public function login(): void

    {
        $logger = new Logger('auth_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Méthode non autorisée.", 405);
        }

        // Récupération des données (username et password)
        $data = json_decode(file_get_contents('php://input'), true);

        // Échappement des données pour éviter les failles XSS
        $username = htmlspecialchars($data['username'] ?? '');
        $password = $data['password'] ?? '';

        // Verification des champs
        if (empty($username) || empty($password)) {
            throw new Exception("Champs requis manquants.", 400);
        }

        // Authentification
        try {
            $user = User::findByUsername($username);
            if ($user && $user->verifyPassword($password)) {
                // Authentification réussie on log l'info
                $logger->info("Authentification réussie pour l'utilisateur: $username");

                // Verification du statut du compte
                if ($user->getAccountStatus() !== 'active') {
                    $logger->warning("Compte inactif pour l'utilisateur: $username");
                    throw new Exception("Compte inactif. Contactez l'administrateur.", 403);
                }

                // on protège la route avec JWT
                $tokenManager = new TokenManager();
                $sequence = [
                    'id' => $user->getUserId(),
                    'username' => $user->getUsername(),
                    // on recupère le role de l'utilisateur pour contrôler l'accès aux ressources
                    'role' => $user->getRoleId()
                ];

                // on génère le token
                $jwt = $tokenManager->generateToken($sequence);
                // on renvoie le token au client
                http_response_code(200);
                echo json_encode(["message" => "Authentification réussie.",
                    "token" => $jwt]);
                exit;

            } else {
                // Échec de l'authentification
                $logger->warning("Échec de l'authentification pour l'utilisateur: $username");
                throw new Exception("Erreur lors de l'authentification.", 401);
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Erreur serveur."]);
            exit;
        } catch (Exception $e) {
            $logger->error("Erreur d'authentification: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["error" => "Erreur de reuperation des données."]);
            exit;
        }
        // fin de l'authentification
    }

    public function logout(): void
    {
        header('Content-Type: application/json');
        // Pour une API RESTful, le logout côté serveur est inutile car javascript(client) va detruire le token.
        http_response_code(200);
        echo json_encode(["message" => "Déconnexion réussie."]);
    }
}