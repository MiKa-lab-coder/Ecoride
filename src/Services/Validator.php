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
    //profils utilisateurs
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    public function validatePassword(string $password): bool
    {
        // Au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial
        $pass = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
        return preg_match($pass, $password) === 1;
    }
    public function validateName(string $name): bool
    {
        // Lettres et espaces, entre 2 et 50 caractères
        $valide = '/^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,50}$/u';
        return preg_match($valide, $name) === 1;
    }
    public function validateFirstname(string $firstname): bool
    {
        // Lettres et espaces, entre 2 et 50 caractères
        $first = '/^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,50}$/u';
        return preg_match($first, $firstname) === 1;
    }

    public function validateUsername(string $username): bool
    {
        // Alphanumérique, entre 3 et 20 caractères
        $use = '/^[a-zA-Z0-9]{3,20}$/';
        return preg_match($use, $username) === 1;
    }

    public function validateBirthdate(string $birthdate): bool
    {
        $date = \DateTime::createFromFormat('d-m-Y', $birthdate);
        return $date && $date->format('d-m-Y') === $birthdate;
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
        return $credit >= $trip_price;
    }

    public function validateDriverRating(int $driver_rating): bool
    {
        return $driver_rating >= 0 && $driver_rating <= 5;
    }
    public function validateAccountStatus(string $account_status): bool
    {
        $valid_statuses = ['active', 'suspended', 'deactivated'];
        return in_array($account_status, $valid_statuses);
    }

    public function validatePhotoUrl(string $photo_url): bool
    {
        return filter_var($photo_url, FILTER_VALIDATE_URL) !== false;
    }

    public function validatePhoto(array $photo): array
    {
        $errors = [];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 3 * 1024 * 1024; // 3MB

        if ($photo['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Erreur lors du téléchargement.";
        }
        if (!in_array($photo['type'], $allowed_types)) {
            $errors[] = "Le format de la photo est invalide.";
        }
        if ($photo['size'] > $max_file_size) {
            $errors[] = "La taille de la photo dépasse la limite de 3MB.";
        }
        return $errors;
    }
    // véhicules
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

    public function validateSeatCapacity(int $seat_capacity): bool
    {
        return $seat_capacity >= 1 && $seat_capacity <= 9;
    }
    public function validateColor(string $color): bool
    {
        $col = '/^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,30}$/u';
        return preg_match($col, $color) === 1;
    }
    public function validateEnergyType(string $energy): bool
    {
        $valid_energies = ['thermique', 'électrique', 'hybride'];
        return in_array($energy, $valid_energies);
    }
    public function validateBrand(string $brand): bool
    {
        $marque = '/^[a-zA-Z\sàâäéèêëçîïôöùûüÿñ-]{2,50}$/u';
        return preg_match($marque, $brand) === 1;
    }
    public function validateModel(string $model): bool
    {
        $mod = '/^[a-zA-Z0-9\sàâäéèêëçîïôöùûüÿñ-]{1,50}$/u';
        return preg_match($mod, $model) === 1;
    }

    // trajets
    public function validateTripName(string $trip_name): bool
    {
        $name = '/^[a-zA-Z0-9\sàâäéèêëçîïôöùûüÿñ_.,!?-]{2,100}$/u';
        return preg_match($name, $trip_name) === 1;
    }
    public function validateDescription(string $description): bool
    {
        $desc = '/^[a-zA-Z0-9\sàâäéèêëçîïôöùûüÿñ_.,!?-]{10,1000}$/u';
        return preg_match($desc, $description) === 1;
    }
    public function validateDepartureOrArrival(string $location): bool
    {
        $loc = '/^[a-zA-Z0-9\sàâäéèêëçîïôöùûüÿñ,.-]{2,100}$/u';
        return preg_match($loc, $location) === 1;
    }

    public function validateTripDate(string $date): bool
    {
        $tripDate = \DateTime::createFromFormat('d-m-Y H:i', $date);
        return $tripDate && $tripDate->format('d-m-Y H:i') === $date;
    }

    public function validateYmdDateFormat(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public function validateTripNature(string $nature): bool
    {
        $valid_natures = ['ecologic', 'standard', 'all'];
        return in_array($nature, $valid_natures);
    }

    public function validateTripPrice(int $price): bool
    {
        return filter_var($price, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) !== false;
    }

    public function validateSeatsAvailable(int $seats): bool
    {
        return $seats >= 1 && $seats <= 9;
    }

    public function validateSmokingAllowed(int $smokingAllowed): bool
    {
        return $smokingAllowed === 0 || $smokingAllowed === 1;
    }
    public function validatePetAllowed(int $petAllowed): bool
    {
        return $petAllowed === 0 || $petAllowed === 1;
    }

    public function validateTime(mixed $arrival_time): bool
    {
        $time = \DateTime::createFromFormat('H:i', $arrival_time);
        return $time && $time->format('H:i') === $arrival_time;
    }
}
