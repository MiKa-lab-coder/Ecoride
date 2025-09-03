<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Validator;
use App\Services\TokenManager;
use DateTime;
use PDOException;
use Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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
        // Initialisation du logger
        $logger = new Logger('register_logger');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));

        header('Content-Type: application/json');

        // Vérification que la requête est bien en POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception("Méthode non autorisée.", 405);
        }
        // Récupération des données et du fichier uploadé
        $data = $_POST;
        $photo = $_FILES['photo'] ?? null;

        // Validator des données
        $validate = new Validator();
        $errors = [];

        // Échappement des données pour éviter les failles XSS
        $name = htmlspecialchars($data['name'] ?? null);
        $firstname = htmlspecialchars($data['firstname'] ?? null);
        $birthdate = htmlspecialchars($data['birthdate'] ?? null);
        $username = htmlspecialchars($data['username'] ?? null);
        $email = htmlspecialchars($data['email'] ?? null);
        $password = $data['password'] ?? null;
        $confirmPassword = $data['confirmPassword'] ?? null;



        // Validator des champs
        if ($validate->validateName($name)) {
            $errors['name'] = 'Nom invalide. Utilisez uniquement des lettres et espaces, entre 2 et 50 caractères.';
        }
        if ($validate->validateFirstname($firstname)) {
            $errors['firstname'] = 'Prénom invalide. Utilisez uniquement des lettres et espaces,
             entre 2 et 50 caractères.';
        }
        if (!$validate->validateBirthdate($birthdate)) {
            $errors['birthdate'] = 'Date de naissance invalide. Utilisez le format JJ-MM-AAAA.';
        } elseif (!$validate->validateAge($birthdate)) {
            $errors['birthdate'] = 'Vous devez avoir au moins 18 ans pour vous inscrire.';
        }
        if (!$validate->validateUsername($username)) {
            $errors['username'] = 'Nom d\'utilisateur invalide. Utilisez uniquement des caractères alphanumériques,
             entre 3 et 20 caractères.';
        }
        if (!$validate->validateEmail($email)) {
            $errors['email'] = 'Email invalide.';
        }
        if (!$validate->validatePassword($password)) {
            $errors['password'] = 'Mot de passe invalide. Il doit contenir au moins 8 caractères, une majuscule,
             une minuscule, un chiffre et un caractère spécial.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirmPassword'] = 'Les mots de passe ne correspondent pas.';
        }
        if ($photo) {
            $photoErrors = $validate->validatePhoto($photo);
            $errors = array_merge($errors, $photoErrors);
        }

        // Si des erreurs de validation existent, on les retourne
        if (!empty($errors)) {
            throw new Exception("Données invalides.", 400);
        }
        //Verification de la disponibilité de l'username et de l'email
        if (User::existsEmail($email)) {
            $errors['email'] = 'Email déjà utilisé.';
        }
        if (User::existUsername($username)) {
            $errors['username'] = 'Nom d\'utilisateur déjà pris.';
        }
        if (!empty($errors)) {
            throw new Exception("Données invalides.", 400);
        }

        //si ok, traitement de l'inscription
        try {
            $photo_path = 'uploads/default.png'; // chemin d'une image par défaut
            if (isset($_FILES['photo']) && ($_FILES['photo']['error'] === UPLOAD_ERR_OK)) {
                $uploadDir = __DIR__ . '/../../public/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $photoName = uniqid() . '_' . basename($_FILES['photo']['name']);
                $uploadFile = $uploadDir . $photoName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
                    $photo_path = '/uploads/' . $photoName;
                } else {
                    $errors[] = 'Échec du téléchargement de la photo de profil.';
                    $logger->error('Échec du téléchargement de la photo de profil.');
                }
            }

            if (!empty($errors)) {
                throw new Exception("Données invalides.", 400);
            }

            // Création de l'utilisateur
            $birthdateFormatted = DateTime::createFromFormat('d-m-Y', $birthdate);
            $user = new User(
                $name,
                $firstname,
                $birthdateFormatted,
                $username,
                $email,
                $password,
                $photo_path, 20,
                0,
                'active',
                3
            );
            // Enregistrement de l'utilisateur en base de données
            if ($user->save()) {
                http_response_code(200);
                $logger->info("Nouvel utilisateur inscrit: $username");
            } else {
                throw new Exception("Erreur lors de l'inscription.", 500);
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO: ' . $e->getMessage());
            throw new Exception("Erreur d'accès serveur.", 500);
        } catch (Exception $e) {
            $logger->error('Erreur inscription: ' . $e->getMessage());
            throw new Exception("Erreur d'inscription en bdd.", 500);
        }
        // fin de l'inscription
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

                // on protège la route avec JWT
                $tokenManager = new TokenManager();
                $sequence = [
                    'id' => $user->getIdUser(),
                    'username' => $user->getUsername(),
                    // on recupère le role de l'utilisateur pour contrôler l'accès aux ressources
                    'role' => $user->getRoleId()
                ];

                // on génère le token
                $jwt = $tokenManager->generateToken($sequence);
                throw new Exception("Authentification réussie.", 200);
                // redirect vers la page d'accueil ou tableau de bord gerer par le front

            } else {
                // Échec de l'authentification
                $logger->warning("Échec de l'authentification pour l'utilisateur: $username");
                throw new Exception("Erreur lors de l'authentification.", 500);
            }
        } catch (PDOException $e) {
            $logger->error('Erreur PDO: ' . $e->getMessage());
            throw new Exception("Erreur serveur.", 500);
        } catch (\Exception $e) {
            $logger->error("Erreur d\'authentification: " . $e->getMessage());
            throw new Exception("Erreur de reuperation des données.", 500);
        }
        // fin de l'authentification
    }

    public function logout(): void
    {
        header('Content-Type: application/json');
        // Pour une API RESTful, le logout côté serveur est inutile car javascript(client) va detruire le token.
        throw new Exception("Déconnexion réussie.", 200);
    }
}