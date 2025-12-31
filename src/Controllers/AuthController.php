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


    /**
     * Méthode pour s'inscrire
     */
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
                $errors['birthdate'] = 'Date de naissance invalide. Utilisez le format AAAA-MM-JJ.';
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

            // De base on prend une photo par défaut
            $photo_path = 'uploads/default.png';
            // Vérification de l'upload de la photo
            if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                // Validation de la photo avec Validator.php
                $photoErrors = $validator->validatePhoto($photo);
                if (!empty($photoErrors)) {
                    // Enregistrement des erreurs dans un tableau
                    $errors = array_merge($errors, $photoErrors);
                } else {
                    // Défini le chemin d'accès de la photo
                    $uploadDir = __DIR__ . '/../../../public/uploads/';
                    // Verifie si le dossier existe, sinon on le crée
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    // Génère un nom de fichier unique en utilisant uniqid() et le nom du fichier original
                    $photoName = uniqid() . '_' . basename($photo['name']);
                    // Chemin complet du fichier
                    $uploadFile = $uploadDir . $photoName;
                    // Déplacement du fichier temporaire vers le répertoire de destination
                    if (move_uploaded_file($photo['tmp_name'], $uploadFile)) {
                        // Défini le chemin d'accès de la photo
                        $photo_path = 'uploads/' . $photoName;
                    } else {
                        // Enregistrement des erreurs dans un tableau
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

            $birthdateFormatted = \DateTime::createFromFormat('Y-m-d', $birthdate);
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
                    null
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

    /**
     * Méthode pour se connecter
     */
    public function login(): void
    {
        $logger = new Logger('auth_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["error" => "Méthode non autorisée."]);
            exit;
        }

        // Récupération des données (username et password)
        $data = json_decode(file_get_contents('php://input'), true);

        // Vérification des champs requis EN DEHORS du try-catch principal
        $username = htmlspecialchars($data['username'] ?? '');
        $password = $data['password'] ?? '';
        if (empty($username) || empty($password)) {
            http_response_code(400);
            echo json_encode(["error" => "Champs requis manquants."]);
            exit;
        }

        // Authentification
        try {
            $user = User::findByUsername($username);
            // --- DEBUG ---
//            if ($user) {
//                echo "User Found. Hashed password from DB: " . $user->getPassword() . "\n";
//                echo "Password from form: " . $password . "\n";
//                echo "Verification result: " . (password_verify($password, $user->getPassword()) ? 'SUCCESS' : 'FAILURE') . "\n"; // Added semicolon
//            } else {
//                echo "User NOT Found for username: " . $username . "\n"; // Added semicolon
//            }
//            exit;
            // --- END DEBUG

            if ($user && $user->verifyPassword($password)) {
                // Authentification réussie
                $logger->info("Authentification réussie pour l'utilisateur: $username");

                // Vérification du statut du compte
                if ($user->getAccountStatus() !== 'active') {
                    $logger->warning("Compte inactif pour l'utilisateur: $username");
                    http_response_code(403);
                    echo json_encode(["error" => "Compte inactif. Contactez l'administrateur."]);
                    exit;
                }

                // Génération du token JWT
                $tokenManager = new TokenManager();
                $sequence = [
                    'id' => $user->getUserId(),
                    'username' => $user->getUsername(),
                    'role' => $user->getRoleId()
                ];
                $jwt = $tokenManager->generateToken($sequence);

                // Envoi de la réponse avec le token
                http_response_code(200);
                echo json_encode(["message" => "Authentification réussie.", "token" => $jwt]);
                exit;

            } else {
                // Échec de l'authentification
                $logger->warning("Échec de l'authentification pour l'utilisateur: $username");
                http_response_code(401);
                echo json_encode(["error" => "Erreur lors de l'authentification."]);
                exit;
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
    }
}
    // Pour la déconnexion, côté client il suffit de supprimer le token JWT stocké (localStorage) avec JavaScript
    // Exemple : localStorage.removeItem('token');