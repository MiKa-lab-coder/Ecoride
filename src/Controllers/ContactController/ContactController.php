<?php
// Gestion du formulaire de contact
namespace App\Controllers\ContactController;

use App\Services\Mailler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Services\Validator;
use Dotenv\Dotenv;

class ContactController
{
    private $mailler;
    private $logger;

    public function __construct()
    {
        $this->mailler = new Mailler();

        // Initialiser le logger
        $this->logger = new Logger('contact_logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../../logs/contact.log', 100));
    }

    // Méthode pour gérer le formulaire de contact (envoi vers le modérateur)
    public function handleContactForm(): void
    {
        // Pas de verfication de token car le formulaire est accessible sans être connecté

        // Récupérer les données JSON de la requête
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $subject = $input['subject'] ?? '';
        $email = $input['email'] ?? '';
        $message = $input['message'] ?? '';

        // Validation des données
        $validator = new Validator();
        $errors = [];
        if (!$validator->validateName($name)) {
            $errors[] = "Le nom est invalide.";
        }
        if (!$validator->validateEmail($email)) {
            $errors[] = "L'email est invalide.";
        }
        if (empty($subject)) {
            $errors[] = "Le sujet est requis.";
        }
        if (empty($message)) {
            $errors[] = "Le message est requis.";
        }
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }
        // Protection contre XSS
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        // Préparer le contenu de l'email

        $to = $_ENV['MODERATOR_EMAIL'];
        $emailSubject = "Nouveau message de contact: " . $subject;
        $emailBody = "Vous avez reçu un nouveau message de contact.\n\n" .
            "Nom: " . $name . "\n" .
            "Email: " . $email . "\n" .
            "Sujet: " . $subject . "\n\n" .
            "Message:\n" . $message;
        // Envoyer l'email
        $sent = $this->mailler->configEmail($to, $emailSubject, $emailBody);
        if ($sent) {
            http_response_code(200);
            echo json_encode(['message' => 'Message envoyé avec succès.']);
            $this->logger->info("Message de contact envoyé par $email avec le sujet '$subject'");
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de l\'envoi du message.']);
            $this->logger->error("Erreur lors de l'envoi du message de contact par $email");
        }
    }
    // techniquement on pourrait ajouter une méthode pour eviter les spams en limitant le nombre de messages par IP ou par email

    /**
     * Honeypot
     * Ajouter un formulaire caché dans le HTML via CSS
     * 2 options :
     * - si le formulaire est rempli et soumis, on laisse mourir la requete (listner qui ne fait rien)
     * - si le formulaire est rempli et soumis, on recupere l'adresse mail,
     * on la stocke dans une liste noire en base de donnée et on bloque les futures requetes venant de cette adresse mail
     *
     * on peut aussi envisager un honeypot agressif, qui renvoi les donnees du formulaire a address mail indiqué,
     * mais il y a un risque de se faire blacklister par les services d'emailing
     */
}