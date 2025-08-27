import{loadHeadFoot} from "./addHeaderFooter.js";
import {setBurger} from "./burger.js";
import {setFilterBtn} from "./filterBtn.js";
import {viewDetails} from "./moreDetails.js";
import {initDashboardButtons} from "./dashboardBTNManager.js";
import { initSectionMenu } from "./sectionMenuManager.js";
import { displaySidebarByRoles } from "./displaySidebarByRoles.js";

//appel de la fonction pour charger l'en-tête et le pied de page
document.addEventListener('DOMContentLoaded',async () => {
    await loadHeadFoot('/Ecoride/public/html/includes/header.html', 'header-placeholder');
    await loadHeadFoot('/Ecoride/public/html/includes/footer.html', 'footer-placeholder');

    //appel de la fonction pour gérer le menu burger
    setBurger();

    //appel de la fonction pour gérer le bouton de filtre
    setFilterBtn();

    //appel de la fonction pour gérer le bouton de détails
    viewDetails();

    //appel de la fonction pour gérer les boutons du dashboard
    initDashboardButtons();

    //appel de la fonction pour gérer le menu de section
    initSectionMenu();

    //role en dur pour test
    const userRole = document.body.dataset.userRole || 'moderator'; // Valeur par défaut 'user' si non définie
    displaySidebarByRoles(userRole);

});