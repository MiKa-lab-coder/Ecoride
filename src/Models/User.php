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
    protected string $table = 'USERS';

    // Attributes
    private ?int $user_id = null;
    private string $name;
    private string $firstname;
    private DateTime $birth_date;
    private string $username;
    private ?string $photo;
    private string $email;
    private string $password;
    private int $total_trips;
    private string $account_status;
    private int $role_id;
    private ?float $driver_rating = null;
    private ?int $credit = null;


    // Getters
    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getBirthDate(): DateTime
    {
        return $this->birth_date;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPhoto(): ?string
    {
        if ($this->photo === null || $this->photo === '') {
            return '/uploads/default.png'; // Chemin par défaut absolu
        }
        // Si le chemin est déjà absolu, le retourner tel quel
        if (strpos($this->photo, '/') === 0) {
            return $this->photo;
        }
        // Si le chemin est relatif (ex: 'uploads/image.png'), le rendre absolu
        return '/' . $this->photo;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getTotalTrips(): int
    {
        return $this->total_trips;
    }

    public function getAccountStatus(): string
    {
        return $this->account_status;
    }

    public function getRoleId(): int
    {
        return $this->role_id;
    }

    public function getDriverRating(): ?float
    {
        return $this->driver_rating;
    }

    public function getCredit(): ?int
    {
        return $this->credit;
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

    public function setBirthDate(DateTime $birth_date): void
    {
        $this->birth_date = $birth_date;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setTotalTrips(int $total_trips): void
    {
        $this->total_trips = $total_trips;
    }

    public function setAccountStatus(string $account_status): void
    {
        $this->account_status = $account_status;
    }

    public function setRoleId(int $role_id): void
    {
        $this->role_id = $role_id;
    }

    public function setDriverRating(?float $rating): void
    {
        $this->driver_rating = $rating;
    }

    public function setCredit(?int $credit): void
    {
        $this->credit = $credit;
    }


    // Constructeur
    public function __construct(string $name, string $firstname, DateTime $birth_date, string $username, ?string $photo,
                                string $email, string $password, int $total_trips,
                                string $account_status = 'active', int $role_id = 3)
    {
        parent::__construct();
        $this->setName($name);
        $this->setFirstname($firstname);
        $this->setBirthDate($birth_date);
        $this->setUsername($username);
        $this->setPhoto($photo);
        $this->setEmail($email);
        $this->password = $password;
        $this->setTotalTrips($total_trips);
        $this->setAccountStatus($account_status);
        $this->setRoleId($role_id);
    }

    public function save(): bool
    {
        $db = Database::getInstance();

        try {
            if ($this->user_id !== null) {
                // Logique pour l'UPDATE
                $stmt = $db->prepare("UPDATE USERS SET name = :name, firstname = :firstname, birth_date = :birth_date,
                    username = :username, photo = :photo, email = :email, password = :password,
                    total_trips = :total_trips, account_status = :account_status, role_id = :role_id WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $this->user_id, \PDO::PARAM_INT);
            } else {
                // Logique pour l'INSERT
                $this->setPassword($this->password); // Hash password only on new user creation
                $stmt = $db->prepare("INSERT INTO USERS (name, firstname, birth_date, username, photo, email, password,
                     total_trips, account_status, role_id) VALUES (:name, :firstname, :birth_date, :username, :photo, :email,
                    :password, :total_trips, :account_status, :role_id)");
            }

            $birthdateStr = $this->birth_date->format('Y-m-d');
            $stmt->bindParam(':name', $this->name, \PDO::PARAM_STR);
            $stmt->bindParam(':firstname', $this->firstname, \PDO::PARAM_STR);
            $stmt->bindParam(':birth_date', $birthdateStr, \PDO::PARAM_STR);
            $stmt->bindParam(':username', $this->username, \PDO::PARAM_STR);
            $stmt->bindParam(':photo', $this->photo, \PDO::PARAM_STR);
            $stmt->bindParam(':email', $this->email, \PDO::PARAM_STR);
            $stmt->bindParam(':password', $this->password, \PDO::PARAM_STR);
            $stmt->bindParam(':total_trips', $this->total_trips, \PDO::PARAM_INT);
            $stmt->bindParam(':account_status', $this->account_status, \PDO::PARAM_STR);
            $stmt->bindParam(':role_id', $this->role_id, \PDO::PARAM_INT);

            $stmt->execute();

            if ($this->user_id === null) {
                $this->user_id = (int)$db->lastInsertId();
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

    // Méthodes statiques
    private static function hydrate(array $data): self
    {
        $user = new self(
            $data['name'],
            $data['firstname'],
            new DateTime($data['birth_date']),
            $data['username'],
            $data['photo'],
            $data['email'],
            $data['password'],
            (int)$data['total_trips'],
            $data['account_status'],
            $data['role_id'] ?? 3
        );
        
        $user->user_id = (int)$data['user_id'];
        if (isset($data['driver_rating'])) {
            $user->setDriverRating((float)$data['driver_rating']);
        }
        if (isset($data['credit'])) {
            $user->setCredit((int)$data['credit']);
        }
        return $user;
    }

    // Trouver un utilisateur par son ID
    public static function find(int $user_id): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
        SELECT 
            u.*,
            (SELECT AVG(r.rating_value) FROM RATINGS r WHERE r.rated_user_id = u.user_id) as driver_rating,
            (SELECT SUM(t.amount) FROM TRANSACTIONS t WHERE t.user_id = u.user_id) as credit
        FROM 
            USERS u
        WHERE 
            u.user_id = :user_id
    ");
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            }
        } catch (PDOException $e) {
            $log = new Logger('find_user_errors');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("erreur lors de récupération de l'utilisateur" . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
        }

        return null;
    }

    public static function findByUsername(string $username): ?self
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM USERS WHERE username = :username");
        $stmt->bindParam(':username', $username, \PDO::PARAM_STR);
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            }
        } catch (PDOException $e) {
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
        $stmt = $db->prepare("SELECT * FROM USERS WHERE email = :email");
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        try {
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($data) {
                return self::hydrate($data);
            }
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
        $stmt = $db->prepare("SELECT COUNT(*) FROM USERS WHERE email = :email");
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
        $stmt = $db->prepare("SELECT COUNT(*) FROM USERS WHERE username = :username");
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
    
    public static function findAll(): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM USERS");
        
        try {
            $stmt->execute();
            $users = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = self::hydrate($data);
            }
            return $users;
        } catch (PDOException $e) {
            $log = new Logger('find_all_users_errors');
            $log->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $log->error("Erreur lors de la récupération de tous les utilisateurs : " . $e->getMessage(),
                ['trace' => $e->getTraceAsString()]);
            return [];
        }
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->getUserId(),
            'name' => $this->getName(),
            'firstname' => $this->getFirstname(),
            'birth_date' => $this->getBirthDate()->format('Y-m-d'),
            'username' => $this->getUsername(),
            'photo' => $this->getPhoto(),
            'email' => $this->getEmail(),
            'total_trips' => $this->getTotalTrips(),
            'account_status' => $this->getAccountStatus(),
            'role_id' => $this->getRoleId(),
            'driver_rating' => $this->getDriverRating(),
            'credit' => $this->getCredit()
        ];
    }

    // Méthodes de sécurité
    public function setPassword(string $password): void
    {
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