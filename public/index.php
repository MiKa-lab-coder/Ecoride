<?php

/**
 * Va etre le routeur principal de l'application, il va rediriger les requetes vers les bons controllers
 * Sur le principe du Single Entry Point.
 */

// On appelle le fichier autoload de composer
require_once __DIR__ . '/../vendor/autoload.php';

// On precise quelle controller seront utilisés
use App\Controllers\AuthController;
use App\Controllers\AdminController\AdminController;
use App\Controllers\BookingController\BookingController;
use App\Controllers\ContactController\ContactController;
use App\Controllers\IssuesController\IssuesController;
use App\Controllers\RatingController\RatingController;
use App\Controllers\ReviewController\ReviewController;
use App\Controllers\TransactionController\TransactionController;
use App\Controllers\TripController\TripController;
use App\Controllers\UserController\UserController;
use App\Controllers\VehicleController\VehicleController;

// Définir les entêtes autorisés pour les requêtes
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Gérer les requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Récupérer Uri et méthode HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// On initialise les controllers
$authController = new AuthController();
$adminController = new AdminController();
$bookingController = new BookingController();
$issuesController = new IssuesController();
$ratingController = new RatingController();
$reviewController = new ReviewController();
$transactionController = new TransactionController();
$tripController = new TripController();
$userController = new UserController();
$vehicleController = new VehicleController();
$contactController = new ContactController();

// Définir les routes sous forme de tableau associatif en fonction des méthodes HTTP
$routes = [
    'GET' => [
        // Auth
        '/api/auth/logout' => [$authController, 'logout'],

        // Admin
        '/api/admin/pending-trips' => [$adminController, 'getPendingTrips'],
        '/api/admin/issues' => [$issuesController, 'viewIssues'],
        '/api/admin/statCredits' => [$transactionController, 'getPlatformStats'],
        '/api/admin/statTrips' => [$tripController, 'getWeeklyTrips'],

        // Booking
        '/api/bookings/user' => [$bookingController, 'getUserBookings'],

        // Rating
        '/api/ratings/user' => [$ratingController, 'getUserRating'],

        // Transaction
        '/api/transactions/credits' => [$transactionController, 'getUserBalance'],

        // Trip
        '/api/trips' => [$tripController, 'getAvailableSeats'],
        '/api/trips/search' => [$tripController, 'searchWithFiltersOrNot'],
        '/api/trips/details' => [$tripController, 'getTripDetails'],
        '/api/trips/user' => [$tripController, 'getUserTrips'],
        '/api/trips/past' => [$tripController, 'getUserCompletedTrips'],

        // User
        '/api/user/profile' => [$userController, 'showMyProfile'],
        '/api/user/photo' => [$userController, 'showMyPhoto'],

        // Vehicle
        '/api/vehicles/user' => [$vehicleController, 'getUserCars'],

    ],
    'POST' => [
        // Auth
        '/api/auth/login' => [$authController, 'login'],
        '/api/auh/registration' => [$authController, 'registration'],
        '/api/auth/forgot-password' => [$authController, 'forgotPassword'],//a faire
        '/api/auth/reset-password' => [$authController, 'resetPassword'], //a faire

        // Admin
        'api/admin/create-account' => [$adminController, 'createAccount'],
        'api/admin/suspend-user' => [$adminController, 'suspendUser'],
        'api/admin/reactivate-user' => [$adminController, 'reactivateUser'],
        'api/admin/change-role' => [$adminController, 'changeUserRole'],
        '/api/admin/approve-trip' => [$adminController, 'approuveTrips'],
        '/api/admin/reject-trip' => [$adminController, 'rejectTrips'],

        // Booking
        '/api/bookings' => [$bookingController, 'bookTrip'],

        // Contact
        '/api/contact' => [$contactController, 'handleContactForm'],

        // Issues
        '/api/issues' => [$issuesController, 'startIssue'],
        '/api/issues/close' => [$issuesController, 'closeIssue'],

        // Rating
        '/api/ratings' => [$ratingController, 'submitRating'],

        // Review
        '/api/reviews' => [$reviewController, 'submitReview'],

        // Transaction
        '/api/transactions' => [$transactionController, 'payTrip'],
        '/api/transactions/refund' => [$transactionController, 'payBackTrip'],

        // Trip
        '/api/trips' => [$tripController, 'proposeTrip'],
        '/api/trips/update' => [$tripController, 'updateTrip'],
        'api/trips/start' => [$tripController, 'startTrip'],
        '/api/trips/end' => [$tripController, 'endTrip'],

        // User
        '/api/user/profile/update' => [$userController, 'updateMyProfile'],
        '/api/user/update-photo' => [$userController, 'updateMyPhoto'],

        // Vehicle
        '/api/vehicles' => [$vehicleController, 'addCar'],

    ],

    'DELETE' => [

        // Booking
        '/api/bookings/cancel' => [$bookingController, 'cancelBooking'],

        // Trip
        '/api/trips/delete' => [$tripController, 'deleteTrip'],

        // Vehicle
        '/api/vehicles/delete' => [$vehicleController, 'deleteCar']

    ],
];
// Vérifier si la route existe et si la méthode est correcte
if (isset($routes[$method]) && array_key_exists($uri, $routes[$method])) {
    // array_key_exists — Vérifie si la clé existe dans le tableau
    $controllerAction = $routes[$method][$uri];
    // Appeler la méthode du controller
    call_user_func($controllerAction);
} else {
    // Route non trouvée
    http_response_code(404);
    echo json_encode(['error' => 'Route non trouvée']);
}