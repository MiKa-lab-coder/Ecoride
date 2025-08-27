export function setFilterBtn() {
    /*
        - Bouton de filtre filtersBtn
        - Bloc affiché/masqué advanced-search-filters
        - Bouton de réinitialisation resetBtn
    */
    const filtersBtn = document.getElementById('filters-btn');
    const advancedSearchFilters = document.getElementById('advanced-search-filters');
    const resetBtn = document.getElementById('reset-filter');

    // Fonction pour gérer l'affichage et le masquage des filtres
    if (filtersBtn && advancedSearchFilters) {
        filtersBtn.addEventListener('click', (event) => {
            event.preventDefault();
            //affiche le conteneur et masque le bouton de filtre pour une meilleure UX
            advancedSearchFilters.classList.remove('js-hidden');
            filtersBtn.classList.add('js-hidden');
        });
    } else {
        console.error('Boutons de filtre ou conteneur introuvables.');
    }

    // on vérifie que le bouton de réinitialisation existe avant d'ajouter un écouteur d'événement
    if (resetBtn) {
        // Fonction pour réinitialiser les filtres et masquer le conteneur au clic sur le bouton d'effacement
        resetBtn.addEventListener('click', (event) => {
            event.preventDefault();

            // Réinitialise les champs des filtres
            const allInputs = advancedSearchFilters.querySelectorAll('input, select, textarea');
            allInputs.forEach(input => {
                switch (input.type) {
                    case 'checkbox':
                    case 'radio':
                        input.checked = false;
                        break;
                    default:
                        input.value = '';
                        break;
                }
            });

            // Ferme le conteneur et réaffiche le bouton de filtre
            advancedSearchFilters.classList.add('js-hidden');
            filtersBtn.classList.remove('js-hidden');
        });
    }
}

