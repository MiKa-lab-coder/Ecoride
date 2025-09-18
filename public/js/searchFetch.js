import {viewDetails} from "./moreDetails";

export function setupSearchFetch() {
    // On commence par récupérer les éléments du formulaire de recherche sur la page d'accueil
    const searchForm = document.getElementById('search-form');
    const searchDeparture = document.getElementById('departure');
    const searchArrival = document.getElementById('arrival');
    const searchDate = document.getElementById('departure_day');

    // On ajoute un écouteur d'événement pour intercepter la soumission du formulaire
    if (searchForm) {
        searchForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            // On ne vérifie pas que les champs de recherche sont remplis, car ils sont requis dans le HTML et
            // nettoyer par le backend.

            // On récupère les valeurs des champs de recherche
            const departure = searchDeparture.value;
            const arrival = searchArrival.value;
            const date = searchDate.value;

            // On stocke les critères de recherche dans le sessionStorage et on redirige vers la page de covoiturage
            sessionStorage.setItem('quickSearch', JSON.stringify({departure, arrival, date}));
            window.location.href = '/html/carpool.html';
        });
    }
}
export function populateAdvancedSearchFetch(){
    // On commence par récupérer les éléments du formulaire de recherche avancée sur la page de covoiturage
    const advancedSearchForm = document.getElementById('advanced-search-form');
    const advancedDeparture = document.getElementById('departure');
    const advancedArrival = document.getElementById('arrival');
    const advancedDate = document.getElementById('departure_day');

    // On vérifie que le formulaire de recherche avancée existe sur la page
    if (advancedSearchForm) {
        // On vérifie si des critères de recherche rapide ont été stockés dans le sessionStorage
        const quickSearch = sessionStorage.getItem('quickSearch');
        if (quickSearch) {
            const { departure, arrival, date } = JSON.parse(quickSearch);
            // On remplit les champs du formulaire de recherche avancée avec les valeurs stockées
            advancedDeparture.value = departure;
            advancedArrival.value = arrival;
            advancedDate.value = date;

            // On supprime les critères de recherche rapide du sessionStorage pour éviter de les réutiliser
            sessionStorage.removeItem('quickSearch');
        }

    }
        // On ne vérifie pas que les champs sont remplis, car ils sont requis dans le HTML et
        // sanitize par le backend.
        // On ne vérifie pas que les filtres sont remplis, parce qu'ils sont optionnels

        // On ajoute un écouteur d'événement pour intercepter la soumission du formulaire de recherche avancée
        advancedSearchForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            // On récupère les valeurs obligatoires des champs de recherche
            const departure = advancedDeparture.value;
            const arrival = advancedArrival.value;
            const date = advancedDate.value;

            // On récupère les valeurs optionnelles des filtres si elles sont remplies
            const carpoolType = document.getElementById('carpool-type').value;
            const departureTime = document.getElementById('time').value;
            const arrivalTime = document.getElementById('time2').value;
            const seats = document.getElementById('seats').value;
            const price = document.getElementById('price').value;
            const rating = document.getElementById('eco-rating').value;
            const petsAllowed = document.getElementById('pets_allowed').checked;
            const smokingAllowed = document.getElementById('luggage_allowed').checked;

            // On construit un objet avec les critères de recherche
            const searchCriteria = {
                departure,
                arrival,
                date,
            };

            // On ajoute les filtres optionnels uniquement s'ils sont remplis
            if (carpoolType) searchCriteria.carpoolType = carpoolType;
            if (departureTime) searchCriteria.departureTime = departureTime;
            if (arrivalTime) searchCriteria.arrivalTime = arrivalTime;
            if (seats) searchCriteria.seats = parseInt(seats);
            if (price) searchCriteria.price = parseFloat(price);
            if (rating) searchCriteria.rating = parseFloat(rating);
            if (petsAllowed) searchCriteria.petsAllowed = petsAllowed;
            if (smokingAllowed) searchCriteria.smokingAllowed = smokingAllowed;

            try {
                // On envoie une requête GEt avec les critères de recherche en tant que paramètres d'URL
                const Params = new URLSearchParams(searchCriteria).toString();
                const response = await fetch(`/api/trips/search?${Params}`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                });

                // On vérifie si la réponse est correcte
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                // On parse la réponse JSON
                const carpoolData = await response.json();
                // On log les données pour matcher les résultats avec displaySearchResults() la première fois
                console.log('Données de covoiturage reçues:', carpoolData);

                // Si aucun covoiturage n'est trouvé, on affiche un message
                if (carpoolData.length === 0) {
                    const resultsContainer = document.getElementById('results-container');
                    const resultsMessage = document.createElement('p');
                    resultsMessage.textContent = "Aucun covoiturage ne correspond à vos critères de recherche," +
                        " essayez avec d'autres critères.";
                    // On vide le conteneur de résultats avant d'afficher le message
                    resultsContainer.innerHTML = '';
                    resultsContainer.appendChild(resultsMessage);
                }
                else {
                    // On affiche les résultats dans le conteneur prévu à cet effet
                    displaySearchResults(carpoolData);
                }

            } catch (error) {
                console.error('Erreur lors de la recherche de covoiturages:', error);
            }
        });
    }

export function displaySearchResults(carpoolData) {
    const resultsContainer = document.getElementById('results-container');
    if (!resultsContainer) {
        console.error("Conteneur des résultats non trouvé.");
        return;
    }

    resultsContainer.innerHTML = ''; // On vide le conteneur

    // On parcourt les données des covoiturages et on crée des cartes pour chaque covoiturage
    // On limite l'affichage aux 10 premiers résultats pour éviter de surcharger la page
    carpoolData.slice(0, 10).forEach(carpool => {
        // Crée la structure principale de la carte
        const card = document.createElement('div');
        card.classList.add('results-card', 'card');
        card.dataset.tripId = carpool.id; // Ajout d'un attribut de données pour identifier le trajet

        // Header
        const header = document.createElement('div');
        header.classList.add('results-card-header');

        // Titre du trajet
        const title = document.createElement('h4');
        title.classList.add('results-card-road');
        title.innerHTML = `${carpool.departure} <span class="arrow">→</span> ${carpool.arrival}`;

        // Info conducteur
        const driverDiv = document.createElement('div');
        driverDiv.classList.add('results-card-driver');

        const driverImg = document.createElement('img');
        driverImg.src = carpool.driverImg;
        driverImg.alt = "image profil conducteur";
        driverImg.classList.add('results-card-img');
        driverImg.width = 80;
        driverImg.height = 80;

        const driverNameP = document.createElement('p');
        driverNameP.innerHTML = `Conducteur : <span class="results-details">${carpool.driverName}</span>`;

        driverDiv.appendChild(driverImg);
        driverDiv.appendChild(driverNameP);
        header.appendChild(title);
        header.appendChild(driverDiv);
        card.appendChild(header);

        // Détails du trajet
        const detailsDiv = document.createElement('div');
        detailsDiv.classList.add('results-card-details');

        // Détails du trajet
        detailsDiv.innerHTML = `
            <p>Départ : <span class="results-details">${carpool.departureTime}</span></p>
            <p>Arrivée : <span class="results-details">${carpool.arrivalTime}</span></p>
            <p>Places restantes : <span class="results-details">${carpool.remainingSeats}</span></p>
            <p>Prix : <span class="results-details">${carpool.price} credits</span></p>
            <p>Type de trajet : <span class="results-details">${carpool.type}</span></p>
            <p>Note Ecorider : <span class="results-details">${carpool.ecoNote} <span class="star">⭐</span></span></p>
        `;

        // Footer
        const footer = document.createElement('div');
        footer.classList.add('ride-footer');
        const detailsBtn = document.createElement('button');
        detailsBtn.classList.add('details-btn');
        detailsBtn.textContent = 'Voir les détails';
        footer.appendChild(detailsBtn);

        card.appendChild(detailsDiv);
        card.appendChild(footer);

        // On ajoute la carte au conteneur des résultats
        resultsContainer.appendChild(card);
    });

    // On initialise les écouteurs d'événements pour les boutons "Voir les détails".
    viewDetails();
}