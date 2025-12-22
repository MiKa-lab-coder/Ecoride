<?php

namespace App\Models;

use App\Models\Database\MongoDatabase;
use MongoDB\Client;
use MongoDB\BSON\ObjectId;

/**
 * Reviews
 * Va gerer les commentaires des utilisateurs. Les commentaires seront stockés dans une base de données noSQL.
 * Ils seront récupérés au besoin et stockés dans la table Issues en cas de litiges, pour avoir une description du problème.
 * La collection Reviews contiendra les champs suivants :
 * -review_id (identifiant unique du commentaire)
 * -user_id (identifiant de l'utilisateur ayant posté le commentaire).
 * -trip_id (identifiant du voyage concerné par le commentaire).
 */
class Review
{
    private ?string $review_id; // ID du commentaire
    private string $user_id; // ID de l'utilisateur ayant posté le commentaire
    private string $trip_id; // ID du voyage concerné par le commentaire
    private string $content; // Contenu du commentaire

    private Client $client; // Instance de la connexion MongoDB

    // getters et setters
    public function getReviewId(): ?string
    {
        return $this->review_id;
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getTripId(): string
    {
        return $this->trip_id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setReviewId(string $review_id): void
    {
        $this->review_id = $review_id;
    }

    public function setUserId(string $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function setTripId(string $trip_id): void
    {
        $this->trip_id = $trip_id;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }


    public function __construct(string $user_id, string $trip_id, string $content, ?string $review_id = null)
    {
        $this->user_id = $user_id;
        $this->trip_id = $trip_id;
        $this->content = $content;
        $this->review_id = $review_id ?? '';

        // Initialiser la connexion MongoDB
        $this->client = MongoDatabase::getInstance();
    }

    // Méthode pour créer un nouveau commentaire
    public function create(): bool
    {
        $reviewsCollection = $this->client->selectCollection('ecoride', 'reviews');
        $document = [
            'user_id' => $this->user_id,
            'trip_id' => $this->trip_id,
            'content' => $this->content,
        ];
        $result = $reviewsCollection->insertOne($document);

        $this->review_id = (string)$result->getInsertedId();

        // Retourner true si l'insertion a réussi, sinon false
        return $result->getInsertedCount() > 0;
    }

    // Méthode pour récupérer un commentaire par son ID(MongoDB ObjectId)
    public function getReviewById(string $review_id): null|static
    {
        $reviewsCollection = $this->client->selectCollection('ecoride', 'reviews');
        $comment = $reviewsCollection->findOne(['_id' => new ObjectId($review_id)]);
        if ($comment) {
            // Création d'une nouvelle instance de Review avec les données trouvées
            return new static(
                (string)$comment->user_id,
                (string)$comment->trip_id,
                (string)$comment->content,
                (string)$comment->_id
            );
        }
        // Si aucun commentaire n'est trouvé, retourner null
        return null;
    }

    /**
     * Récupère le contenu d'un commentaire pour un trajet et un auteur donnés.
     */
    public static function getReviewComment(int $tripId, int $authorId): ?string
    {
        $client = MongoDatabase::getInstance();
        $reviewsCollection = $client->selectCollection('ecoride', 'reviews');

        $review = $reviewsCollection->findOne([
            'trip_id' => (string)$tripId,
            'user_id' => (string)$authorId
        ]);

        return $review ? $review['content'] : null;
    }
}
