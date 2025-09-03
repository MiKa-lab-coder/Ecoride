<?php

namespace App\Models;

use App\Models\Database\Database;
use DateTime;
use PDO;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class User extends BaseModel
{
    /**
     * @var string Le nom de la table associée au modèle
     */
    protected string $table = 'users';

    // Attributes
    private ?int $id_user = null;
    private string $name;
    private string $firstname;
    private DateTime $birthdate;
    private string $username;
    private string $photo;
    private string $email;
    private string $password;
    private int $credit;
    private int $driver_rating;
    private string $account_status;
    private int $role_id;

    // Getters
    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getBirthdate(): DateTime
    {
        return $this->birthdate;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPhoto(): string
    {
        return $this->photo;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCredit(): int
    {
        return $this->credit;
    }

    public function getDriverRating(): int
    {
        return $this->driver_rating;
    }

    public function getAccountStatus(): string
    {
        return $this->account_status;
    }
    public function getRoleId(): int
    {
        return $this->role_id;
    }

    // Setters
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function setBirthdate(DateTime $birthdate): void
    {
        $this->birthdate = $birthdate;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPhoto(string $photo): void
    {
        $this->photo = $photo;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setCredit(int $credit): void
    {
        $this->credit = $credit;
    }

    public function setDriverRating(int $driver_rating): void
    {
        $this->driver_rating = $driver_rating;
    }

    public function setAccountStatus(string $account_status): void
    {
        $this->account_status = $account_status;
    }
    public function setRoleId(int $role_id): void
    {
        $this->role_id = $role_id;
    }

    // Constructeur
    public function __construct(string $name, string $firstname, DateTime $birthdate, string $username, string $photo,
                                string $email, string $password, int $credit, int $driver_rating, string $account_status, int $role_id = 3)
    {
        parent::__construct();
        $this->name = $name;
        $this->firstname = $firstname;
        $this->birthdate = $birthdate;
        $this->username = $username;
        $this->photo = $photo;
        $this->email = $email;
        $this->setPassword($password);
        $this->credit = $credit;
        $this->driver_rating = $driver_rating;
        $this->account_status = $account_status;
        $this->role_id = $role_id;
    }


    //toutes les verifications de champs, de format, d'email, de mot de passe etc
    //seront faites dans le controller avant d'instancier un objet User
    //car le model ne doit pas se soucier de la logique metier
    //il doit juste representer la table en bdd et fournir les methodes CRUD
    //et eventuellement des methodes specifiques au model

    // Méthodes statiques

    private static function hydrate(array $data): self
    {
        $user = new self(
            $data['name'],
            $data['firstname'],
            new DateTime($data['birthdate']),
            $data['username'],
            $data['photo'],
            $data['email'],
            $data['password'],
            (int)$data['credit'],
            (int)$data['driver_rating'],
            $data['account_status'],
            $data['role_id'] ?? 3 // Valeur par défaut '3/utilisateur' si non fournie
        );
        $user->id_user = (int)$data['id_user'];
        return $user;
    }

    public static function find(int $id_user): ?self
    {
        $db = Database::getInstance();
        //preparation
        $stmt = $db->prepare("SELECT * FROM users WHERE id_user = :id_user");
        $stmt->bindParam(':id_user', $id_user, \PDO::PARAM_INT);
        //execution
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            //si l'utilisateur existe
            if ($data) {
                return self::hydrate($data);
            }
        } catch (PDOException $e) {
            //ajout d'un log
            $log = new Logger('find_user_errors');
            //ecriture dans le fichier
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("erreur lors de récupération de l'utilisateur" . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
        }

        return null;
    }
    public static function findByUsername(string $username): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            }
        } catch (PDOException $e) {
            // Log d'erreur
            $log = new Logger('find_username_errors');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("Erreur lors de la récupération du nom d'utilisateur: " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
        }
        return null;
    }

    public static function findByEmail(string $email): ?self
    {
        $db = Database::getInstance();
        //preparation
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        //execution
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            //si l'email existe
            if ($data) {
                return self::hydrate($data);
            }
            //si l'email n'existe pas
        } catch (PDOException $e) {
            $log = new Logger('find_mail_errors');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("erreur lors de la récupération de l'email" . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
        }
        return null;
    }

    public static function existsEmail(string $email): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        try {
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            $log = new Logger('exists_email_errors');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("Erreur lors de la vérification de l'email : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    public static function existUsername(string $username): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        try {
            $stmt->execute();
            $count = (int)$stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            $log = new Logger('exists_username_errors');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("Erreur lors de la vérification du nom d'utilisateur : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    // Méthodes d'instance
    public function save(): bool
    {
        // Connexion
        $db = Database::getInstance();

        try {
            // Logique pour l'UPDATE
            if ($this->id_user !== null) {
                $sql = "UPDATE users SET name = :name, firstname = :firstname, birthdate = :birthdate,
                    username = :username, photo = :photo, email = :email, password = :password, credit = :credit,
                    driver_rating = :driver_rating, account_status = :account_status WHERE id_user = :id_user, role_id = :role_id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':id_user', $this->id_user, \PDO::PARAM_INT);
            } else {
                // Logique pour l'INSERT
                $sql = "INSERT INTO users (name, firstname, birthdate, username, photo, email, password,
                    credit, driver_rating, account_status) VALUES (:name, :firstname, :birthdate, :username, :photo, :email,
                    :password, :credit, :driver_rating, :account_status, :role_id)";
                $stmt = $db->prepare($sql);
            }

            // Liaison des paramètres
            $birthdateStr = $this->birthdate->format('Y-m-d');
            $stmt->bindParam(':name', $this->name, \PDO::PARAM_STR);
            $stmt->bindParam(':firstname', $this->firstname, \PDO::PARAM_STR);
            $stmt->bindParam(':birthdate', $birthdateStr, \PDO::PARAM_STR);
            $stmt->bindParam(':username', $this->username, \PDO::PARAM_STR);
            $stmt->bindParam(':photo', $this->photo, \PDO::PARAM_STR);
            $stmt->bindParam(':email', $this->email, \PDO::PARAM_STR);
            $stmt->bindParam(':password', $this->password, \PDO::PARAM_STR);
            $stmt->bindParam(':credit', $this->credit, \PDO::PARAM_INT);
            $stmt->bindParam(':driver_rating', $this->driver_rating, \PDO::PARAM_INT);
            $stmt->bindParam(':account_status', $this->account_status, \PDO::PARAM_STR);
            $stmt->bindParam(':role_id', $this->role_id, \PDO::PARAM_INT); // si on ajoute le role_id

            // Exécution de la requête
            $stmt->execute();

            // Récupération du dernier ID si c'était une insertion
            if ($this->id_user === null) {
                $this->id_user = (int)$db->lastInsertId();
            }

            return true;

        } catch (\PDOException $e) {
            $log = new Logger('save_user_errors');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("Erreur lors de la sauvegarde de l'utilisateur : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return false;
        }
    }

    // Méthodes de sécurité
    public function setPassword(string $password): void
    {
        // PASSWORD_DEFAULT utilise l'algorithme le plus fort disponible
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool
    {
        if (password_verify($password, $this->password)) {
            return true;
        }
        return false;
    }
}

