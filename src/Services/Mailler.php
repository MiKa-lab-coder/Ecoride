<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class Mailler
 * On utilise PHPMailer pour envoyer des emails.
 * On utilise Monolog pour logger les erreurs.
 * Va permettre la gestion des emails du site.
 */
class Mailler
{
    private $mailer;
    private $logger;


    public function __construct()
    {
        // Initialisation du logger
        $this->logger = new Logger('mailler');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/mailler.log', 400));


        // Initialisation de PHPMailer à completer apres obtention des identifiants SMTP
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.example.com'; // Remplacez par votre serveur SMTP
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = ''; // Remplacez par votre utilisateur SMTP
        $this->mailer->Password = ''; // Remplacez par votre mot de passe SMTP
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587; // Port SMTP
        $this->mailer->setFrom('mail@mail.fr', 'Ecoride');// méthode native de PHPMailer

    }

    /**
     * Envoie un email.
     *
     * @param string $to L'adresse email du destinataire.
     * @param string $subject Le sujet de l'email.
     * @param string $body Le contenu de l'email.
     * @return bool Vrai si l'email a été envoyé avec succès, faux sinon.
     */

    public function configEmail(string $to, string $subject, string $body): bool
    {
        try {
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            $send = $this->mailer->send();
            if ($send) {
                $this->logger->info("Email envoyé à $to avec le sujet '$subject'");
            }

            // Nettoyer les adresses pour le prochain envoi
            $this->mailer->clearAddresses();

            // Retourner le résultat de l'envoi
            return $send;

        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'envoi de l'email: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour envoyer un email de confirmation d'inscription
    public function sendComfirmationMail(string $to, string $username): bool
    {
        $subject = "Confirmation d'inscription";
        $body = "<h1>Bienvenue, $username!</h1><br><p>Merci pour votre inscription sur notre plateforme.</p>";
        return $this->configEmail($to, $subject, $body);
    }

    /**
     * @param array $recipients Liste des adresses email des destinataires
     * @param string $subject
     * @param string $body
     * @return bool
     */

    // Méthode pour envoyer un email auto pour signalement de litige
    public function sendAutoReportMail(string $to, string $username, string $userId): bool
    {
        $subject = "Signalement de litige par un utilisateur";
        $body = "<h1>Bonjour modérateur !</h1><br>
    <p>Un litige a été signalé par l'utilisateur $username (ID: $userId) après un trajet.</p>
    <br><p>Merci de bien vouloir examiner ce litige.</p>";

        return $this->configEmail($to, $subject, $body);
    }


    // Méthode pour envoyer de demande d'information supplémentaire
    public function needInfoMail(string $to, string $username): bool
    {
        $subject = "Informations supplémentaires requises";
        $body = "<h1>Bonjour, $username!</h1><br><p>Nous aurions besoin de quelques informations supplémentaires
        pour la gestion de votre litige.</p><br>
        <p>Pouvez-vous nous contacter via notre formulaire.</p>";
        return $this->configEmail($to, $subject, $body);
    }

    //Méthode pour envoyer un email de réponse suite litige ou a contact
    public function sendResponseMail(string $to, string $username, string $response): bool
    {
        $subject = "Réponse à votre litige";
        $body = "<h1>Bonjour, $username!</h1><br>
        <p>Voici la réponse à votre litige :</p><br>
        <p>$response</p>";
        return $this->configEmail($to, $subject, $body);
    }

    // Méthode pour envoyer un email a l'admin
    public function sendAdminSuspendMail(string $to, string $username): bool
    {
        $subject = "Nouvelle inscription sur le site";
        $body = "<h1>Bonjour, Admin !</h1><br>
        <p>Le compte de $username a enfreint les règles de la plateforme.</p><br>
        <p>Pouvez-vous supendre son Compte</p>";
        return $this->configEmail($to, $subject, $body);
    }

    // Méthode pour envoyer un email pour signaler la fin d'un trajet
    public function sendEndRideMail(string $to, string $username): bool
    {
        $subject = "Fin de trajet";
        $body = "<h1>Bonjour, $username!</h1><br>
        <p>Votre trajet est terminé. Merci d'avoir utilisé notre plateforme.</p><br>
        <p>N'hésitez pas à laisser un avis sur votre expérience.</p>";
        return $this->configEmail($to, $subject, $body);
    }

}