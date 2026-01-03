<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use App\Models\Trip;

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
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        // Initialisation du logger
        $this->logger = new Logger('mailler');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/mailler.log', 400));


        // Initialisation de PHPMailer à completer apres obtention des identifiants SMTP
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['MAIL_HOST'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['MAIL_USERNAME'];  //a mettre a jour
        $this->mailer->Password = $_ENV['MAIL_PASSWORD'];  //a mettre a jour
        // Définir le type de chiffrement en fonction de la configuration
        if ($_ENV['MAIL_ENCRYPTION'] === 'null' || $_ENV['MAIL_ENCRYPTION'] === '') {
            $this->mailer->SMTPSecure = ''; // Pas de chiffrement
        } else {
            $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        }

        $this->mailer->Port = $_ENV['MAIL_PORT'];
        $this->mailer->setFrom($_ENV['MAIL_USERNAME'], $_ENV['MAIL_FROM_NAME']);

    }

    /**
     * Envoie un email.
     *
     * @param string $to L'adresse email du destinataire.
     * @param string $subject Le sujet de l'email.
     * @param string $body Le contenu de l'email.
     * @return bool Vrai si l'email a été envoyé avec succès, faux sinon.
     */

    /**
     * Configuration de l'envoi d'email.
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

    /**
     * Méthode pour envoyer un email de confirmation d'inscription.
     */
    public function sendComfirmationMail(string $to, string $username): bool
    {
        $subject = "Confirmation d'inscription";
        $body = "<h1>Bienvenue, $username!</h1><br><p>Merci pour votre inscription sur notre plateforme.</p><br>
        <p>Nous sommes heureux de vous offrir 20 crédits pour profiter de nos offres<p>";

        return $this->configEmail($to, $subject, $body);
    }

    /**
     * @param array $recipients Liste des adresses email des destinataires
     * @param string $subject
     * @param string $body
     * @return bool
     */

    /**
     * Mail de signalement de litige
     */
    public function sendAutoReportMail(string $username, string $userId): bool
    {
        $to = $_ENV['MODERATOR_EMAIL'];
        $subject = "Signalement de litige par un utilisateur";
        $body = "<h1>Bonjour modérateur !</h1><br>
    <p>Un litige a été signalé par l'utilisateur $username (ID: $userId) après un trajet.</p>
    <br><p>Merci de bien vouloir examiner ce litige.</p>";

        return $this->configEmail($to, $subject, $body);
    }


//    // Méthode pour envoyer de demande d'information supplémentaire
//    public function needInfoMail(string $to, string $username): bool
//    {
//        $subject = "Informations supplémentaires requises";
//        $body = "<h1>Bonjour, $username!</h1><br><p>Nous aurions besoin de quelques informations supplémentaires
//        pour la gestion de votre litige.</p><br>
//        <p>Pouvez-vous nous contacter via notre formulaire.</p>";
//        return $this->configEmail($to, $subject, $body);
//    }

    /**
     * Mail de suspension de compte
     */
    public function sendUserSuspendMail(string $to, string $username): bool
    {
        $subject = "Compte utilisateur suspendu";
        $body = "<h1>Bonjour, $username!</h1><br>
        <p>Votre compte a enfreint les règles de la plateforme.</p><br>
        <p>Il a été suspendu, vous pouvez nous contater via notre formulaire.</p><br>
        <p></p>";
        return $this->configEmail($to, $subject, $body);
    }

    /**
     * Mail de reactivation de compte
     */
    public function sendUserReactivateMail(string $to, string $username): bool
    {
        $subject = "Compte utilisateur réactivé";
        $body = "<h1>Bonjour, $username!</h1><br>
        <p>Votre compte a été réactivé.</p><br>
        <p>Merci pour votre patience</p>";
        return $this->configEmail($to, $subject, $body);
    }


    /**
     * Mail de fin de trajet
     */
    public function sendEndRideMail(string $to, string $username): bool
    {
        $subject = "Fin de trajet";
        $body = "<h1>Bonjour, $username!</h1><br>
        <p>Votre trajet est terminé. Merci d'avoir utilisé notre plateforme.</p><br>
        <p>N'hésitez pas à laisser un avis sur votre expérience.</p>";
        return $this->configEmail($to, $subject, $body);
    }

    /**
     * Mail d'annulation/suppression de trajet
     */
    public function sendTripCancellationMail(string $to, string $passengerName, Trip $trip): bool
    {
        $subject = "Annulation de votre trajet";
        $body = "
            <h1>Bonjour, $passengerName!</h1>
            <p>Nous sommes au regret de vous informer que le trajet suivant a été annulé par le conducteur :</p>
            <ul>
                <li><strong>Départ :</strong> " . $trip->getDepartureLocation() . "</li>
                <li><strong>Arrivée :</strong> " . $trip->getArrivalLocation() . "</li>
                <li><strong>Date :</strong> " . $trip->getDepartureDay()->format('d/m/Y') . "</li>
            </ul>
            <p>Vous avez été intégralement remboursé de " . $trip->getTripPrice() . " crédits.</p>
            <p>Nous nous excusons pour ce désagrément.</p>
        ";
        return $this->configEmail($to, $subject, $body);
    }

    /**
     * Mail de rejet de trajet
     */
    public function sendTripRejectionMail(string $to, string $passengerName): bool
    {
        $subject = "Rejet de votre trajet";
        $body = "
            <h1>Bonjour, $passengerName!</h1>
            <p>Nous sommes au regret de vous informer que le trajet suivant a été rejeté par le moderateur :</p>
            ";
        return $this->configEmail($to, $subject, $body);
    }
}