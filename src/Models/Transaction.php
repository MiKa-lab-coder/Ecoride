<?php

namespace App\Models;

use App\Models\Database\Database;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDO;

/**
 * Class Transaction
 * Gère les transactions financières entre utilisateurs.
 */

class Transaction extends BaseModel
{
    protected string $table = 'TRANSACTIONS';
    private int $transaction_id; // ID de la transaction
    private int $user_id; // ID de l'utilisateur effectuant la transaction
    private int $amount; // Montant de la transaction en chiffres entiers
    private string $transaction_type; // Pour savoir si c'est un paiement ou frais de plateforme
    private int $reference; // Point de référence de la transaction (trajet)

    //getter et setters
    public function getTransactionId(): int
    {
        return $this->transaction_id;
    }
    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getTransactionType(): string
    {
        return $this->transaction_type;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setTransactionId(int $transaction_id): void
    {
        $this->transaction_id = $transaction_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function setTransactionType(string $transaction_type): void
    {
        $this->transaction_type = $transaction_type;
    }

    public function setReference(string $reference): void
    {
        $this->reference = $reference;
    }

    public function __construct(int $user_id, int $amount, string $transaction_type, int $reference)
    {
        // pas de transaction_id dans le constructeur, car il est auto-increment
        parent::__construct();
        $this->user_id = $user_id;
        $this->amount = $amount;
        $this->transaction_type = $transaction_type;
        $this->reference = $reference;
    }

    // Méthode pour enregistrer une transaction dans la base de données
    public function save(): bool
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO TRANSACTIONS (user_id, amount, transaction_type, reference)
            VALUES (:user_id, :amount, :transaction_type, :reference)");
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':amount', $this->amount);
            $stmt->bindParam(':transaction_type', $this->transaction_type);
            $stmt->bindParam(':reference', $this->reference);
            return $stmt->execute();
        } catch (Exception $e) {
            $logger = new Logger('transaction_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 100));
            $logger->error('Error saving transaction: ' . $e->getMessage());
            return false;
        }
    }
    // Méthode pour recupérer le solde d'un utilisateur
    public static function getUserBalance(int $user_id): float
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT SUM(amount) as balance FROM TRANSACTIONS WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)$result['balance'] ?? 0;
        } catch (Exception $e) {
            $logger = new Logger('transaction_logger');
            $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', 400));
            $logger->error('Error fetching user balance: ' . $e->getMessage());
            return 0;
        }
    }
}