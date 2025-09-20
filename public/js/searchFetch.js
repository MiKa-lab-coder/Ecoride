// Gestion de la recherche rapide sur la page d'accueil
//import {parseJwt} from "./JwtTool";

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
            }
        }
    });
}
// Remplir le formulaire de recherche avancée avec les critères de recherche rapide
// et pour gérer la soumission du formulaire de recherche avancée
export async function populateAdvancedSearchFetch() {
    // Remplir les champs dès que la page est chargée
    const advancedSearchForm = document.getElementById('advanced-search-form');
    if (advancedSearchForm) {
        const advancedDeparture = document.getElementById('departure');
        const advancedArrival = document.getElementById('arrival');
        const advancedDate = document.getElementById('departure_day');

        const quickSearch = sessionStorage.getItem('quickSearch');
        if (quickSearch) {
            const {departure, arrival, date} = JSON.parse(quickSearch);

            // On s'assure que les éléments existent avant de les manipuler
            if (advancedDeparture && advancedArrival && advancedDate) {
                advancedDeparture.value = departure;
                advancedArrival.value = arrival;
                advancedDate.value = date;
            } else {
                console.warn("Champs de recherche avancée non trouvés.");
            }
            sessionStorage.removeItem('quickSearch');
        }
    }
    // On ne vérifie pas que les champs sont remplis, car ils sont requis dans le HTML et
    // sanitize par le backend.
    // On ne vérifie pas que les filtres sont remplis, parce qu'ils sont optionnels

    // On ajoute un écouteur d'événement pour intercepter la soumission du formulaire de recherche avancée
    if (advancedSearchForm) {
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
                } else {
                    // On affiche les résultats dans le conteneur prévu à cet effet
                    displaySearchResults(carpoolData);
                }

            } catch (error) {
                console.error('Erreur lors de la recherche de covoiturages:', error);
            }
        });
    }
}
// Affichage des résultats de la recherche de covoiturages
export function displaySearchResults(carpoolData) {
    const resultsContainer = document.getElementById('results-container');
    if (!resultsContainer) {
        console.warn("Conteneur des résultats non trouvé.");
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

        // Header card
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

        // Footer card
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
}
// Ajout des écouteurs d'événements aux boutons "Voir les détails" après le rendu des résultats
export function viewDetails() {
    const detailButtons = document.querySelectorAll('.details-btn');
    // On vérifie que des boutons existent avant d'ajouter les écouteurs.
    if (detailButtons.length > 0) {
        detailButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                window.open("../html/details.html", "_self");
            });
        });
    }
}
// Affichage des details d'un covoiturage dans une page dédiée
export async function displayCarpoolDetails() {
    // On récupère l'ID du trajet depuis l'URL de la page
    const Params = new URLSearchParams(window.location.search);
    const tripId = Params.get('tripId');
    const detailsContainer = document.getElementById('details-container');

    // Si l'ID ou le conteneur n'existe pas, on arrête
    if (!tripId || !detailsContainer) {
        console.warn("ID du trajet manquant ou conteneur non trouvé.");
        return;
    }

    try {
        // On envoie une requête GET pour récupérer les détails du covoiturage
        const response = await fetch(`/api/trips/${tripId}`, {
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
        const carpoolDetails = await response.json();
        console.log('Détails du covoiturage reçus:', carpoolDetails);

        // On remplit le conteneur avec les détails du covoiturage
        // On crée le conteneur des détails (la carte)
        const detailsCard = document.createElement('div');
        detailsCard.classList.add('details-content', 'card');

        // Section Chauffeur
        const driverTitle = document.createElement('h2');
        driverTitle.textContent = 'Chauffeur';
        detailsCard.appendChild(driverTitle);

        const driverNameP = document.createElement('p');
        driverNameP.innerHTML = `<strong>Nom:</strong> ${carpoolDetails.driverName}`;
        detailsCard.appendChild(driverNameP);

        const carTypeP = document.createElement('p');
        carTypeP.innerHTML = `<strong>Type de voiture : </strong>${carpoolDetails.carType} (${carpoolDetails.carEnergy})`;
        detailsCard.appendChild(carTypeP);

        const preferencesP = document.createElement('p');
        preferencesP.innerHTML = `<strong>Préférences : </strong>${carpoolDetails.preferences}`;
        detailsCard.appendChild(preferencesP);

        // Section Trajet
        const tripTitle = document.createElement('h2');
        tripTitle.classList.add('middle');
        tripTitle.textContent = 'Trajet';
        detailsCard.appendChild(tripTitle);

        const departureP = document.createElement('p');
        departureP.innerHTML = `<strong>Départ:</strong> ${carpoolDetails.departureCity}, ${carpoolDetails.departureLocation}`;
        detailsCard.appendChild(departureP);

        const arrivalP = document.createElement('p');
        arrivalP.innerHTML = `<strong>Arrivée:</strong> ${carpoolDetails.arrivalCity}, ${carpoolDetails.arrivalLocation}`;
        detailsCard.appendChild(arrivalP);

        const dateTimeP = document.createElement('p');
        dateTimeP.innerHTML = `<strong>Date et Heure : </strong>${carpoolDetails.date}, ${carpoolDetails.time}`;
        detailsCard.appendChild(dateTimeP);

        // Section Tarif
        const priceTitle = document.createElement('h2');
        priceTitle.textContent = 'Tarif';
        detailsCard.appendChild(priceTitle);

        const priceP = document.createElement('p');
        priceP.innerHTML = `<strong>Prix par passager : </strong>${carpoolDetails.price} credits`;
        detailsCard.appendChild(priceP);

        // Bouton de réservation
        const reserveBtn = document.createElement('button');
        reserveBtn.classList.add('reserve-btn');
        reserveBtn.textContent = 'Réserver ce trajet';

        // Ajout de l'écouteur d'événement pour le bouton de réservation
        reserveBtn.addEventListener('click', () => {
            const token = localStorage.getItem('token');
            if (!token) {
                // On prévient l'utilisateur qu'il doit se connecter
                alert('Vous devez être connecté pour réserver un trajet. Vous allez être redirigé vers la page de connexion.');
                // On stocke l'URL de la page de détails dans le sessionStorage pour y revenir après login.
                sessionStorage.setItem('redirectAfterLogin', window.location.href);
                window.location.href = '/html/connexion.html';
            } else {
                // Si l'utilisateur est connecté on fetch la methode de réservation
                const userId = parseJwt(token).userId;
                // On envoie la requete de réservation
                fetch(`/api/bookings/${tripId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({userId})
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        alert('Réservation réussie !');
                        // On redirige vers le dashboard
                        window.location.href = '/html/dashboard.html';
                    })
                    .catch(error => {
                        console.error('Erreur lors de la réservation :', error);
                        alert('Erreur lors de la réservation. Veuillez réessayer plus tard.');
                    });
            }
        });
        detailsCard.appendChild(reserveBtn);

        // On vide le conteneur principal et on y ajoute la nouvelle carte
        detailsContainer.innerHTML = '';
        detailsContainer.appendChild(detailsCard);

    } catch (error) {
        console.error('Erreur lors de la récupération des détails du covoiturage:', error);
        detailsContainer.innerHTML = "Une erreur est survenue lors du chargement des détails.";
    }
}
