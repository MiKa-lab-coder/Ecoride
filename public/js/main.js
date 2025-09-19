// Importation des modules nécessaires
import { loadHeadFoot } from "./addHeaderFooter.js";
import { setBurger } from "./burger.js";
import { setFilterBtn } from "./filterBtn.js";
import { initDashboardButtons } from "./dashboardBTNManager.js";
import { initSectionMenu } from "./sectionMenuManager.js";
import {createDashboardLink, displaySidebarByRoles} from "./displayDashboard.js";
import {setupRegistrationFetch} from "./registrationFetch.js";
import {setupLoginFetch} from "./loginFetch.js";
import {displaySearchResults, populateAdvancedSearchFetch, setupSearchFetch, viewDetails, displayCarpoolDetails} from "./searchFetch.js";
import {parseJwt} from "./JwtTool.js";

localStorage.setItem('token', 'votre-token-de-test');// Pour tester la creation du lien vers le dashboard
// Fonction principale pour initialiser l'application
async function initApp() {
    // Charger le header
    await loadHeadFoot('/html/includes/header.html', 'header-placeholder');
    // Afficher le lien du tableau de bord si l'utilisateur est connecté
    await createDashboardLink();
    // Charger le footer
    await loadHeadFoot('/html/includes/footer.html', 'footer-placeholder');

    // Exécuter tous les scripts apres le chargement du header et footer et de la gestion du lien du tableau de bord
    setBurger();
    setFilterBtn();
    viewDetails();
    initDashboardButtons();
    initSectionMenu();
    displaySidebarByRoles();
    await setupRegistrationFetch();
    await setupLoginFetch();
    setupSearchFetch();
    await populateAdvancedSearchFetch();
    displaySearchResults();
    await displayCarpoolDetails();
    parseJwt();


    // Rôle en dur pour les tests
    //const userRole = document.body.dataset.userRole || 'admin';
    //displaySidebarByRoles(userRole);
}

// Lancer l'initialisation de l'application une fois que le DOM est chargé
document.addEventListener('DOMContentLoaded', initApp);
