<?php

namespace App\Services;

/**
 * Class Validator
 * contient les méthodes de validation des différents champs
 * Ne verifie pas la securité des entrées, mais s'assure qu'elles sont conformes aux attentes
 * La sécurité est gérée dans les controllers (XSS, JWT) et dans les methodes de classe (requetes préparées))
 * Utilisé dans AuthController et UserController
 */
class Validator
{

    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    public function validatePassword(string $password): bool
    {
        // Au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial
        $pass = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        return preg_match($pass, $password) === 1;
        //penser a l'ajout de texte pour prevenir les utilisateurs
    }
    public function validateName(string $name): bool
    {
        // Lettres et espaces, entre 2 et 50 caractères
        $valide = '^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,50}$';
        return mb_ereg_match($valide, $name) === 1;
    }
    public function validateFirstname(string $firstname): bool
    {
        // Lettres et espaces, entre 2 et 50 caractères
        $first = '^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,50}$';
        return mb_ereg_match($first, $firstname) === 1;
    }

    public function validateUsername(string $username): bool
    {
        // Alphanumérique, entre 3 et 20 caractères
        $use = '/^[a-zA-Z0-9]{3,20}$/';
        return preg_match($use, $username) === 1;
        //penser a l'ajout de texte pour prevenir les utilisateurs
    }

    public function validateBirthdate(string $birthdate): bool
    {
        $date = \DateTime::createFromFormat('d-m-Y', $birthdate);
        return $date && $date->format('d-m-Y') === $birthdate;
        //voir comment s'assurer du format en bdd
    }
    public function validateAge(string $birthdate): bool
    {
        $date = \DateTime::createFromFormat('d-m-Y', $birthdate);
        if (!$date) {
            return false;
        }
        $now = new \DateTime();
        $age = $now->diff($date)->y;
        return $age >= 18; // L'utilisateur doit avoir au moins 18 ans pour s'inscrire
    }

    public function validateCredit(int $credit, int $trip_price): bool
    {
        // s'assurer que les crdits sont >= au prix du trajet
        return $credit >= $trip_price;
        //ne pas utiliser de float pour l'instant
    }

    public function validateDriverRating(int $driver_rating): bool
    {
        return $driver_rating >= 0 && $driver_rating <= 5;
        //pas de demi points pour l'instant
    }
    public function validateAccountStatus(string $account_status): bool
    {
        // s'assurer que le status est dans une liste definie
        $valid_statuses = ['active', 'suspended', 'deactivated'];
        return in_array($account_status, $valid_statuses);
    }

    public function validatePhotoUrl(string $photo_url): bool
    {
        // s'assurer que c'est une url valide
        return filter_var($photo_url, FILTER_VALIDATE_URL) !== false;
    }

    public function validatePhoto(array $photo): array
    {
        $errors = [];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 3 * 1024 * 1024; // 3MB

        // Vérifier les erreurs de téléchargement
        if ($photo['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur lors du téléchargement.";
        }
        // Vérifier le type de fichier
        if (!in_array($photo['type'], $allowed_types)) {
            $errors[] = "Le format de la photo est invalide.";
        }
        // Vérifier la taille du fichier
        if ($photo['size'] > $max_file_size) {
            $errors[] = "La taille de la photo dépasse la limite de 3MB.";
        }
        return $errors;
    }
    // Valider le numéro d'immatriculation (format français standard)
    public function validateRegistrationNumber(string $registration): bool
    {
        $reg = '/^[A-Z]{2}-\d{3}-[A-Z]{2}$/';
        return preg_match($reg, $registration) === 1;
    }

    public function validateDateFormat(string $date): bool
    {
        $date = \DateTime::createFromFormat('d-m-Y', $date);
        return $date && $date->format('d-m-Y') === $date;
    }

    // Valider la capacité d'accueil (entre 1 et 9 sièges maximum pour un véhicule standard)
    public function validateSeatCapacity(int $seat_capacity): bool
    {
        return $seat_capacity >= 1 && $seat_capacity <= 9;
    }
    public function validateColor(string $color): bool
    {
        // Lettres et espaces, entre 2 et 30 caractères
        $col = '^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,30}$';
        return mb_ereg_match($col, $color) === 1;
    }
    // Valider le type d'énergie (thermique, électrique, hybride)
    public function validateEnergyType(string $energy): bool
    {
        $valid_energies = ['thermique', 'électrique', 'hybride'];
        return in_array($energy, $valid_energies);
    }
    // Valider la marque (lettres et espaces, entre 2 et 50 caractères)
    public function validateBrand(string $brand): bool
    {
        $marque = '^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,50}$';
        return mb_ereg_match($marque, $brand) === 1;
    }
    // Valider le modèle (lettres, chiffres et espaces, entre 1 et 50 caractères)
    public function validateModel(string $model): bool
    {
        $mod = '^[a-zA-Z0-9\sàâäéèêëçîïôöùûüÿñ-]{1,50}$';
        return mb_ereg_match($mod, $model) === 1;
    }
}
