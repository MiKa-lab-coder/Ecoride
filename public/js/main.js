import{loadHeadFoot} from "./addHeaderFooter.js";
import {setBurger} from "./burger.js";

//execution au chargement de la page
document.addEventListener('DOMContentLoaded',async () => {
    await loadHeadFoot('/Ecoride/public/html/includes/header.html', 'header-placeholder');
    await loadHeadFoot('/Ecoride/public/html/includes/footer.html', 'footer-placeholder');
    setBurger();
});