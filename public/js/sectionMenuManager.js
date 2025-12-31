/**
 * Gestion du menu de section.
 */
const links = document.querySelectorAll('a[data-action]');
const sections = document.querySelectorAll('.dashboard-section');

//fonction pour gérer le menu de section
export function initSectionMenu() {
    //on parcourt les liens et on attache un écouteur d'événement
    links.forEach(link => {
        link.addEventListener('click', (event) => {
            //on empêche le comportement par défaut du lien
            event.preventDefault();
            //on cache toutes les sections en ajoutant la classe js-hidden
            sections.forEach(section => {
                section.classList.remove('active');
            });
            //on récupère la valeur de data-action pour cibler la section correspondante
            const idTarget = link.dataset.action;
            const sectionTarget = document.getElementById(idTarget);
            //on affiche la section ciblée en supprimant la classe js-hidden
            if (sectionTarget) {

                sectionTarget.classList.add('active');
            }
        });
    });
}