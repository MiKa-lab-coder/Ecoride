/**
 * Gestion des filtres de recherche.
 */
export function setFilterBtn() {
    const filtersBtn = document.getElementById('filters-btn');
    const advancedSearchFilters = document.getElementById('advanced-search-filters');
    const resetBtn = document.getElementById('reset-filter');

    // Vérifie si TOUS les éléments nécessaires sont présents sur la page.
    // Cela garantit que la fonctionnalité complète des filtres est disponible.
    if (filtersBtn && advancedSearchFilters && resetBtn) {

        // Fonction pour gérer l'affichage et le masquage des filtres.
        filtersBtn.addEventListener('click', (event) => {
            event.preventDefault();
            // Affiche le conteneur et masque le bouton de filtre pour une meilleure UX.
            advancedSearchFilters.classList.remove('js-hidden');
            filtersBtn.classList.add('js-hidden');
        });

        // Fonction pour réinitialiser les filtres et masquer le conteneur.
        resetBtn.addEventListener('click', (event) => {
            event.preventDefault();

            // Réinitialise tous les champs de filtres à l'intérieur du conteneur,sans utiliser form.reset().
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

            // Ferme le conteneur et réaffiche le bouton de filtre.
            advancedSearchFilters.classList.add('js-hidden');
            filtersBtn.classList.remove('js-hidden');
        });
    } else {
        // Si un ou plusieurs éléments sont manquants, le script se termine silencieusement.
        console.warn('Élément(s) pour les filtres non trouvé(s). Le script se termine silencieusement.');
    }
}

