// Importation des modules nécessaires
import { loadHeadFoot } from "./addHeaderFooter.js";
import { setBurger } from "./burger.js";
import { setFilterBtn } from "./filterBtn.js";
import { viewDetails } from "./moreDetails.js";
import { initDashboardButtons } from "./dashboardBTNManager.js";
import { initSectionMenu } from "./sectionMenuManager.js";
import { displaySidebarByRoles } from "./displaySidebarByRoles.js";
import {setupRegistrationFetch} from "./registrationFetch.js";
import {setupLoginFetch} from "./loginFetch.js";
import {displaySearchResults, populateAdvancedSearchFetch, setupSearchFetch} from "./searchFetch.js";

// Fonction principale pour initialiser l'application
async function initApp() {
    // Charger l'en-tête et le pied de page
    await loadHeadFoot('/html/includes/header.html', 'header-placeholder');
    await loadHeadFoot('/html/includes/footer.html', 'footer-placeholder');

    // Exécuter tous les scripts apres le chargement de l'en-tête et du pied de page
    setBurger();
    setFilterBtn();
    viewDetails();
    initDashboardButtons();
    initSectionMenu();
    setupRegistrationFetch();
    setupLoginFetch();
    setupSearchFetch();
    populateAdvancedSearchFetch();
    displaySearchResults();


    // Rôle en dur pour les tests
    const userRole = document.body.dataset.userRole || 'admin';
    displaySidebarByRoles(userRole);
}

// Lancer l'initialisation de l'application une fois que le DOM est chargé
document.addEventListener('DOMContentLoaded', initApp);
