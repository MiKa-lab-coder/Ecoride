<?php
namespace App\Models;
use App\Models\Database\Database;
use PDO;

abstract class BaseModel
{
    /**
     * @var PDO l'instance de connection a la bdd
     */
    protected PDO $db;

    /**
     * @var string le nom de table associé au model
     */
    protected string $table;
    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }
}