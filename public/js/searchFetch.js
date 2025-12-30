/**
 * Gestion de la recherche de covoiturages.
 */
export function setupSearchFetch() {
    // On attache l'écouteur au document, qui est toujours présent.
    document.addEventListener('submit', (event) => {
        // On vérifie si l'élément qui a déclenché l'événement est bien le formulaire de recherche.
        if (event.target && event.target.id === 'search-form') {
            event.preventDefault();

            // Une fois que l'on sait que c'est le bon formulaire, on récupère les champs.
            const searchDeparture = document.getElementById('departure');
            const searchArrival = document.getElementById('arrival');
            const searchDate = document.getElementById('departure_day');

            if (searchDeparture && searchArrival && searchDate) {
                const departure = searchDeparture.value;
                const arrival = searchArrival.value;
                const date = searchDate.value;

                sessionStorage.setItem('quickSearch', JSON.stringify({departure, arrival, date}));
                window.location.href = '/html/carpool.html';

                // Vidange du formulaire de recherche rapide
                searchDeparture.value = '';
                searchArrival.value = '';
                searchDate.value = '';
            }
        }
    });
}

/**
 * Remplissage automatique du formulaire de recherche avancée.
 */
export async function populateAdvancedSearchFetch() {
    const advancedSearchForm = document.getElementById('advanced-search-form');
    if (!advancedSearchForm) return;

    const advancedDeparture = document.getElementById('departure');
    const advancedArrival = document.getElementById('arrival');
    const advancedDate = document.getElementById('departure_day');

    // Écouteur pour la soumission du formulaire de recherche avancée
    advancedSearchForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const departure = advancedDeparture.value;
        const arrival = advancedArrival.value;
        const date = advancedDate.value;

        const carpoolType = document.getElementById('carpool-type').value;
        const departureTime = document.getElementById('time').value;
        const arrivalTime = document.getElementById('time2').value;
        const seats = document.getElementById('seats').value;
        const price = document.getElementById('price').value;
        const rating = document.getElementById('eco-rating').value;
        const petsAllowed = document.getElementById('pet-allowed').value;
        const smokingAllowed = document.getElementById('smoking-allowed').value;

        const searchCriteria = { departure, arrival, departure_day: date };

        if (carpoolType) searchCriteria.ecologic = (carpoolType === 'ecologic');
        if (departureTime) searchCriteria.departureTime = departureTime;
        if (arrivalTime) searchCriteria.arrivalTime = arrivalTime;
        if (seats) searchCriteria.seats = parseInt(seats);
        if (price) searchCriteria.max_price = parseFloat(price);
        if (rating) searchCriteria.min_rating = parseFloat(rating);
        if (petsAllowed) searchCriteria.animal_pref = (petsAllowed === 'true');
        if (smokingAllowed) searchCriteria.smoking_pref = (smokingAllowed === 'true');

        try {
            const Params = new URLSearchParams(searchCriteria).toString();
            const response = await fetch(`/api/trips/search?${Params}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const carpoolData = await response.json();
            //console.log('Données de covoiturage reçues:', carpoolData);

            const resultsContainer = document.getElementById('results-container');
            resultsContainer.innerHTML = ''; // Vider le conteneur

            if (carpoolData.message) {
                const resultsMessage = document.createElement('p');
                resultsMessage.textContent = carpoolData.message;
                resultsContainer.appendChild(resultsMessage);
            } else {
                displaySearchResults(carpoolData);
            }

        } catch (error) {
            console.error('Erreur lors de la recherche de covoiturages:', error);
        }

        // On referme le panneau de filtres pour une meilleure UX
        const advancedSearchFilters = document.getElementById('advanced-search-filters');
        const filtersBtn = document.getElementById('filters-btn');
        if (advancedSearchFilters && filtersBtn) {
            advancedSearchFilters.classList.add('js-hidden');
            filtersBtn.classList.remove('js-hidden');
        }
    });

    // Vérifier s'il y a une recherche rapide en attente dans sessionStorage
    const quickSearch = sessionStorage.getItem('quickSearch');
    if (quickSearch) {
        const {departure, arrival, date} = JSON.parse(quickSearch);

        if (advancedDeparture && advancedArrival && advancedDate) {
            advancedDeparture.value = departure;
            advancedArrival.value = arrival;
            advancedDate.value = date;

            // Déclencher la soumission du formulaire pour lancer la recherche automatiquement
            advancedSearchForm.requestSubmit();
        }
        sessionStorage.removeItem('quickSearch');
    }
}

/**
 * Affiche les résultats de la recherche de covoiturages.
 */
export function displaySearchResults(data) {
    const resultsContainer = document.getElementById('results-container');
    if (!resultsContainer) {
        console.warn("Conteneur des résultats non trouvé.");
        return;
    }

    resultsContainer.innerHTML = ''; // On vide le conteneur

    // Gestion de la date alternative
    if (data.alternative_date) {
        const alternativeMessage = document.createElement('div');
        alternativeMessage.className = 'alternative-date-message';
        alternativeMessage.innerHTML = `
            <p>Aucun trajet disponible pour la date demandée.</p>
            <p>Prochain trajet disponible le : <strong>${new Date(data.alternative_date).toLocaleDateString()}</strong></p>
            <button id="search-alternative-btn">Rechercher pour cette date</button>
        `;
        resultsContainer.appendChild(alternativeMessage);

        document.getElementById('search-alternative-btn').addEventListener('click', () => {
            const dateInput = document.getElementById('departure_day');
            if (dateInput) {
                dateInput.value = data.alternative_date;
                document.getElementById('advanced-search-form').requestSubmit();
            }
        });
        return;
    }

    const carpoolData = data.trips || [];

    if (!carpoolData || carpoolData.length === 0) {
        const resultsMessage = document.createElement('p');
        resultsMessage.textContent = "Aucun covoiturage ne correspond à vos critères de recherche.";
        resultsContainer.appendChild(resultsMessage);
        return;
    }

    // On parcourt les données des covoiturages et on crée des cartes pour chaque covoiturage
    // On limite l'affichage aux 10 premiers résultats pour éviter de surcharger la page
    carpoolData.slice(0, 10).forEach(carpool => {
        // Crée la structure principale de la carte
        const card = document.createElement('div');
        card.classList.add('results-card', 'card');
        card.dataset.tripId = carpool.trip_id;

        // Header card
        const header = document.createElement('div');
        header.classList.add('results-card-header');

        // Titre du trajet
        const title = document.createElement('h4');
        title.classList.add('results-card-road');
        title.innerHTML = `${carpool.departure_location} <span class="arrow">→</span> ${carpool.arrival_location}`;

        // Info conducteur
        const driverDiv = document.createElement('div');
        driverDiv.classList.add('results-card-driver');

        const driverImg = document.createElement('img');
        driverImg.src = carpool.driver_photo || '../img/bx-user.svg';
        driverImg.alt = "image profil conducteur";
        driverImg.classList.add('results-card-img');
        driverImg.width = 80;
        driverImg.height = 80;

        const driverNameP = document.createElement('p');
        driverNameP.innerHTML = `Conducteur : <span class="results-details">${carpool.driver_firstname || 'N/A'} ${carpool.driver_name || ''}</span>`;

        driverDiv.appendChild(driverImg);
        driverDiv.appendChild(driverNameP);
        header.appendChild(title);
        header.appendChild(driverDiv);
        card.appendChild(header);

        // Détails du trajet
        const detailsDiv = document.createElement('div');
        detailsDiv.classList.add('results-card-details');
        const rating = carpool.driver_rating ? parseFloat(carpool.driver_rating).toFixed(1) : 'N/A';
        
        detailsDiv.innerHTML = `
            <p>Départ : <span class="results-details">${carpool.departure_time}</span></p>
            <p>Arrivée : <span class="results-details">${carpool.arrival_time}</span></p>
            <p>Places restantes : <span class="results-details">${carpool.seating}</span></p>
            <p>Prix : <span class="results-details">${carpool.trip_price} credits</span></p>
            <p>Type de trajet : <span class="results-details">${carpool.trip_nature}</span></p>
            <p>Note Conducteur : <span class="results-details">${rating} <span class="star">⭐</span></span></p>
        `;

        // Footer card
        const footer = document.createElement('div');
        footer.classList.add('ride-footer');
        const detailsBtn = document.createElement('button');
        detailsBtn.classList.add('details-btn');
        detailsBtn.textContent = 'Voir les détails';
        
        // Ajout de l'écouteur d'événement pour stocker les données et rediriger
        detailsBtn.addEventListener('click', () => {
            sessionStorage.setItem('carpoolDetails', JSON.stringify(carpool));
            window.location.href = `../html/details.html?tripId=${carpool.trip_id}`;
        });

        footer.appendChild(detailsBtn);

        card.appendChild(detailsDiv);
        card.appendChild(footer);

        resultsContainer.appendChild(card);
    });
}

/**
 * Affiche les détails d'un covoiturage dans une carte.
 */
function renderCarpoolDetails(carpoolDetails, detailsContainer) {
    // On vide le conteneur principal
    detailsContainer.innerHTML = '';

    // On crée le conteneur des détails (la carte)
    const detailsCard = document.createElement('div');
    detailsCard.classList.add('details-content', 'card');
    detailsCard.style.position = 'relative'; // Pour le positionnement du bouton X

    // Bouton de fermeture (X)
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;'; // Caractère "X"
    closeBtn.classList.add('close-btn');
    // Style pour le bouton X
    Object.assign(closeBtn.style, {
        position: 'absolute',
        top: '10px',
        right: '15px',
        fontSize: '24px',
        lineHeight: '1',
        background: 'none',
        border: 'none',
        cursor: 'pointer',
        padding: '0',
        color: 'black'
    });
    closeBtn.addEventListener('click', (e) => {
        e.preventDefault();
        window.history.back();
    });
    detailsCard.appendChild(closeBtn);


    // Section Chauffeur
    const driverTitle = document.createElement('h2');
    driverTitle.textContent = 'Chauffeur';
    detailsCard.appendChild(driverTitle);

    const driverImg = document.createElement('img');
    driverImg.src = carpoolDetails.driver_photo || '../img/bx-user.svg';
    driverImg.alt = "Photo du conducteur";
    driverImg.style.width = '100px';
    driverImg.style.height = '100px';
    driverImg.style.borderRadius = '50%';
    driverImg.style.objectFit = 'cover';
    detailsCard.appendChild(driverImg);

    const driverNameP = document.createElement('p');
    driverNameP.innerHTML = `<strong>Nom:</strong> ${carpoolDetails.driver_firstname || ''} ${carpoolDetails.driver_name || 'Non disponible'}`;
    detailsCard.appendChild(driverNameP);

    const carTypeP = document.createElement('p');
    carTypeP.innerHTML = `<strong>Type de voiture : </strong>${carpoolDetails.brand || 'N/A'} ${carpoolDetails.model || ''} (${carpoolDetails.energy_type || 'N/A'})`;
    detailsCard.appendChild(carTypeP);

    const preferencesP = document.createElement('p');
    preferencesP.innerHTML = `<strong>Préférences : </strong> Fumeur: ${carpoolDetails.smoking_pref ? 'Oui' : 'Non'}, Animaux: ${carpoolDetails.animal_pref ? 'Oui' : 'Non'}`;
    detailsCard.appendChild(preferencesP);

    // Section Trajet
    const tripTitle = document.createElement('h2');
    tripTitle.classList.add('middle');
    tripTitle.textContent = 'Trajet';
    detailsCard.appendChild(tripTitle);

    const departureP = document.createElement('p');
    departureP.innerHTML = `<strong>Départ:</strong> ${carpoolDetails.departure_location}`;
    detailsCard.appendChild(departureP);

    const arrivalP = document.createElement('p');
    arrivalP.innerHTML = `<strong>Arrivée:</strong> ${carpoolDetails.arrival_location}`;
    detailsCard.appendChild(arrivalP);

    const dateTimeP = document.createElement('p');
    dateTimeP.innerHTML = `<strong>Date et Heure : </strong>${carpoolDetails.departure_day}, ${carpoolDetails.departure_time}`;
    detailsCard.appendChild(dateTimeP);

    // Section Avis
    const reviewsTitle = document.createElement('h2');
    reviewsTitle.textContent = 'Avis sur le conducteur';
    detailsCard.appendChild(reviewsTitle);

    const reviewsContainer = document.createElement('div');
    reviewsContainer.classList.add('reviews-container');
    if (carpoolDetails.driver_reviews && carpoolDetails.driver_reviews.length > 0) {
        carpoolDetails.driver_reviews.forEach(review => {
            const reviewP = document.createElement('p');
            reviewP.innerHTML = `<strong>${review.author_name || 'Anonyme'} (${review.rating}★):</strong> <em>"${review.content}"</em>`;
            reviewsContainer.appendChild(reviewP);
        });
    } else {
        reviewsContainer.innerHTML = '<p>Aucun avis pour ce conducteur.</p>';
    }
    detailsCard.appendChild(reviewsContainer);


    // Section Tarif
    const priceTitle = document.createElement('h2');
    priceTitle.textContent = 'Tarif';
    detailsCard.appendChild(priceTitle);

    const priceP = document.createElement('p');
    priceP.innerHTML = `<strong>Prix par passager : </strong>${carpoolDetails.trip_price} credits`;
    detailsCard.appendChild(priceP);

    // Conteneur pour les boutons
    const buttonContainer = document.createElement('div');
    buttonContainer.classList.add('ride-footer');
    buttonContainer.style.display = 'flex';
    buttonContainer.style.justifyContent ='center';

    // Bouton de réservation
    const reserveBtn = document.createElement('button');
    reserveBtn.classList.add('reserve-btn');
    reserveBtn.textContent = 'Réserver';

    // Ajout de l'écouteur d'événement pour le bouton de réservation
    reserveBtn.addEventListener('click', () => {
        const token = localStorage.getItem('token');
        if (!token) {
            alert('Vous devez être connecté pour réserver un trajet. Vous allez être redirigé vers la page de connexion.');
            sessionStorage.setItem('redirectAfterLogin', window.location.href);
            window.location.href = '/html/connexion.html';
        } else {
            // si l'utilisateur est connecter
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            const decoded = JSON.parse(jsonPayload);
            const userId = decoded.data.id;

            if (confirm(`Confirmez-vous la réservation de ce trajet pour ${carpoolDetails.trip_price} crédits ?`)) {
                fetch('/api/bookings', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({ 
                        trip_id: carpoolDetails.trip_id,
                        user_id: userId
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.error || 'Erreur'); });
                    }
                    return response.json();
                })
                .then(data => {
                    alert('Réservation réussie !');
                    window.location.href = '/html/dashboard.html';
                })
                .catch(error => {
                    console.error('Erreur lors de la réservation :', error);
                    alert('Erreur lors de la réservation : ' + error.message);
                });
            }
        }
    });
    buttonContainer.appendChild(reserveBtn);

    detailsCard.appendChild(buttonContainer);

    detailsContainer.appendChild(detailsCard);
}
/**
 * Affiche les détails d'un trajet dans une page avec détails
 */
export async function displayCarpoolDetails() {
    const detailsContainer = document.getElementById('details-container');
    if (!detailsContainer) {
        console.warn("Conteneur des détails non trouvé.");
        return;
    }

    const params = new URLSearchParams(window.location.search);
    const tripId = params.get('tripId');
    const storedDetails = sessionStorage.getItem('carpoolDetails');

    // Utilisation des données de sessionStorage pour éviter une nouvelle requête API
    if (storedDetails) {
        const carpoolDetails = JSON.parse(storedDetails);
        // On vérifie que les détails stockés correspondent bien au trajet demandé
        if (carpoolDetails.trip_id == tripId) {
            console.log('Détails du covoiturage récupérés depuis sessionStorage:', carpoolDetails);
            renderCarpoolDetails(carpoolDetails, detailsContainer);
            // On nettoie le sessionStorage après utilisation
            sessionStorage.removeItem('carpoolDetails');
            return;
        }
    }
    
    // Si l'ID du trajet n'est pas dans l'URL, on ne peut rien faire
    if (!tripId) {
        console.warn("ID du trajet manquant dans l'URL.");
        detailsContainer.innerHTML = "Détails du trajet non disponibles.";
        return;
    }

    // Si les détails ne sont pas dans le sessionStorage, on les récupère via l'API
    console.log("Récupération des détails du covoiturage depuis l'API...");
    try {
        const response = await fetch(`/api/trips/details?trip_id=${tripId}`, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' },
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const carpoolDetails = await response.json();
        //console.log('Détails du covoiturage reçus depuis lAPI:', carpoolDetails);
        renderCarpoolDetails(carpoolDetails, detailsContainer);

    } catch (error) {
        console.error('Erreur lors de la récupération des détails du covoiturage:', error);
        detailsContainer.innerHTML = "Une erreur est survenue lors du chargement des détails.";
    }
}
