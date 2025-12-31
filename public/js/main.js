// Importation des modules nécessaires
import { loadHeadFoot } from "./addHeaderFooter.js";
import { setBurger } from "./burger.js";
import { setFilterBtn } from "./filterBtn.js";
//import { initDashboardButtons } from "./dashboardBTNManager.js";
import { initSectionMenu } from "./sectionMenuManager.js";
import { createDashboardLink, displayDashboardContentByRoles } from "./displayDashboard.js";
import { setupRegistrationFetch } from "./registrationFetch.js";
import { setupLoginFetch } from "./loginFetch.js";
import { displaySearchResults, populateAdvancedSearchFetch, setupSearchFetch, displayCarpoolDetails } from "./searchFetch.js";
import { parseJwt } from "./JwtTool.js";
import { logout } from "./logoutFetch.js";
import { fetchPastTrips, fetchBooking, fetchOfferedTrip} from "./dashboard1Fecth.js";
import { displayUserProfil } from "./dashboard2Fetch.js";
import { fetchReceivedReviews } from "./dashboard3Fetch.js";
import { displayUserCar } from "./dashboard4Fetch.js";
import { reviewOffer, reviewReports, fetchPendingReviews } from "./dashboardModeratorFetch.js";
import { manageAdminActions } from "./dashboardAdmin1Fetch.js";
import { statTrips, statCredit } from "./dashboardAdmin2Fetch.js";


// Fonction principale pour initialiser l'application
async function initApp() {
    // Les fonctions qui s'exécutent sur TOUTES les pages
    await loadHeadFoot('/html/includes/header.html', 'header-placeholder');
    await loadHeadFoot('/html/includes/footer.html', 'footer-placeholder');
    setBurger();

    // Récupérer le nom de la page actuelle
    const pageName = window.location.pathname.split('/').pop();

    switch (pageName) {
        case 'index.html':
        case '':
            // Logique de la page d'accueil
            await createDashboardLink();
            setupSearchFetch();
            break;

        case 'carpool.html':
            // Logique de la page de covoiturage
            await createDashboardLink();
            setFilterBtn()
            await populateAdvancedSearchFetch();
            break;

        case 'details.html':
            // Logique de la page des détails d'un covoiturage
            await createDashboardLink();
            await displayCarpoolDetails();
            break;

        case 'contact.html':
            // Logique de la page de contact
            await createDashboardLink();
            break;

        case 'connexion.html':
            // Logique de la page de connexion
            await setupLoginFetch();
            break;

        case 'registration.html':
            // Logique de la page d'inscription
            await setupRegistrationFetch();
            break;

        case 'dashboard.html':
            // Logique du tableau de bord
            displayDashboardContentByRoles();
            initSectionMenu();
            //initDashboardButtons();
            
            const userRole = localStorage.getItem('userRole');

            // Fonctions communes à tous les utilisateurs connectés (y compris admin et modo)
            if (userRole) {
                // profil utilisateur et véhicule
                await displayUserProfil();
                await displayUserCar();

                // recuperation des trajets
                await fetchBooking();
                await fetchOfferedTrip();
                await fetchPastTrips();
                
                // récupération des avis
                await fetchReceivedReviews();
            }

            // Fonctions spécifiques Modérateur (2) et Admin (1)
            if (userRole === '2' || userRole === '1') {
                await reviewOffer();
                await reviewReports();
                await fetchPendingReviews();
            }

            // Fonctions spécifiques Admin (1)
            if (userRole === '1') {
                await manageAdminActions();
                await statTrips();
                await statCredit();
            }

            // Ajouter un écouteur d'événement pour le bouton de déconnexion
            const logoutBtn = document.getElementById('logout-button');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    logout();
                });
            }
            break;

        default:
            // Logique par défaut ou page 404
            console.warn("Page non gérée.");
            break;
    }
}

// Lancer l'initialisation de l'application une fois que le DOM est chargé
document.addEventListener('DOMContentLoaded', initApp);