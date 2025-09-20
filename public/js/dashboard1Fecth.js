// Mise en place de fetch pour le dashboard (affiche des infos : profil, mes covoiturages, mes réservations, etc...)


// historique des trajets
export async function fetchPastTrips() {
    // Récupérer le token JWT depuis le stockage local
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Aucun token trouvé dans le localStorage');
        return;
    }

    try {
        const response = await fetch('/api/trips/past', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP! statut: ${response.status}`);
        }

        const pastTrips = await response.json();

        //console.log('Trajets passés:', pastTrips); // Pour vérifier les données reçues

        const pastTripsContainer = document.getElementById('past-trips-container');
        if (!pastTripsContainer) {
            console.error('Élément conteneur non trouvé.');
            return;
        }

        // Boucler sur les trajets passés et les afficher
        pastTrips.forEach(trip => {
            const tripCard = document.createElement('div');
            tripCard.classList.add('trip-card');
            tripCard.innerHTML = `
                <p>Titre trajet : ${trip.title}</p>
                <p>Départ : ${trip.departure}</p>
                <p>Arrivée : ${trip.arrival}</p>
                <p>Date : ${new Date(trip.date).toLocaleDateString()}</p>
                <p>Prix en crédit : ${trip.price} </p>
            `;
            pastTripsContainer.appendChild(tripCard);
        });

    } catch (error) {
        console.error('Erreur lors de la récupération des trajets passés:', error);
    }
}

// Afficher les réservations
export async function fetchBooking() {
    // Récupérer le token JWT depuis le stockage local
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Aucun token trouvé dans le localStorage');
        return;
    }
    // Faire une requête Fetch pour obtenir les réservations de l'utilisateur
    try {
        const response = await fetch('/api/bookings/user', {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        if (!response.ok) {
            throw new Error(`Erreur HTTP! statut: ${response.status}`);
        }

        const bookings = await response.json();
        //console.log('Réservations:', bookings); // Pour vérifier les données reçues

        const bookingsContainer = document.getElementById('bookings-container');
        if (!bookingsContainer) {
            console.error('Élément conteneur non trouvé.');
            return;
        }

        // Boucler sur les réservations et les afficher
        bookings.forEach(booking => {
            const bookingCard = document.createElement('div');
            bookingCard.classList.add('booking-card');
            bookingCard.innerHTML = `
                <p>Titre trajet : ${booking.tripTitle}</p>
                <p>Départ : ${booking.departure}</p>
                <p>Arrivée : ${booking.arrival}</p>
                <p>Date : ${new Date(booking.date).toLocaleDateString()}</p>
                <p>Heure de départ : ${booking.departureTime}</p>
                <p>Heure d'arrivée : ${booking.arrivalTime}</p>
                <p>Type de trajet : ${booking.tripNature}</p>
                <p>Conducteur : ${booking.driverName}</p>
                <p>Prix en crédit : ${booking.price} </p>
                <select id="rating-select-${booking.id}" class="rating-select">
                    <option value="">Noter le trajet</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                <br>
                <textarea id="review-textarea-${booking.id}" class="review-textarea" placeholder="Laisser un commentaire"></textarea>
                <br>
                <input type="button" class="end-trip-btn" value="Terminer">
                <input type="button" class="cancel-booking-btn" value="Annuler">
                <input type="button" class="send-issues-btn" value="Signaler">
            `;
            bookingsContainer.appendChild(bookingCard);

            // Gérer les écouteurs d'événements à l'extérieur du HTML
            const endTripBtn = bookingCard.querySelector('.end-trip-btn');
            const cancelBookingBtn = bookingCard.querySelector('.cancel-booking-btn');
            const sendIssuesBtn = bookingCard.querySelector('.send-issues-btn');

            // Écouteur pour "Terminer trajet"
            endTripBtn.addEventListener('click', async () => {
                const ratingSelect = bookingCard.querySelector('.rating-select');
                const reviewTextarea = bookingCard.querySelector('.review-textarea');
                const rating = ratingSelect.value;
                const review = reviewTextarea.value;

                try {
                    const response = await fetch(`/api/trips/${booking.tripId}/end`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify({ rating, review, bookingId: booking.id })
                    });
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP! statut: ${response.status}`);
                    }
                    console.log('Trajet terminé avec succès!');
                } catch (error) {
                    console.error('Erreur lors de la terminaison du trajet:', error);
                }
            });

            // Écouteur pour "Annuler"
            cancelBookingBtn.addEventListener('click', async () => {
                try {
                    const response = await fetch(`/api/bookings/${booking.id}/cancel`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    });
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP! statut: ${response.status}`);
                    }
                    console.log('Réservation annulée avec succès!');
                } catch (error) {
                    console.error('Erreur lors de l\'annulation de la réservation:', error);
                }
            });

            // Écouteur pour "Signaler"
            sendIssuesBtn.addEventListener('click', async () => {
                const reviewTextarea = bookingCard.querySelector('.review-textarea');
                const issues = reviewTextarea.value;

                try {
                    const response = await fetch(`/api/issues`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify({ issues, bookingId: booking.id }) // Ajouter l'ID de la réservation
                    });
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP! statut: ${response.status}`);
                    }
                    console.log('Problème signalé avec succès!');
                } catch (error) {
                    console.error('Erreur lors du signalement du problème:', error);
                }
            });
        });

    } catch (error) {
        console.error('Erreur lors de la récupération des réservations:', error);
    }
}

// Afficher les covoiturages proposés
export async function fetchOfferedTrip() {
    // Récupérer le token JWT depuis le stockage local
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Aucun token trouvé dans le localStorage');
        return;
    }

    try {
        const response = await fetch('/api/trips/user', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP! statut: ${response.status}`);
        }

        const offeredTrips = await response.json();
        const offeredTripsContainer = document.getElementById('offered-trips-container');

        if (!offeredTripsContainer) {
            console.error('Élément conteneur non trouvé.');
            return;
        }

        // Boucler sur les covoiturages proposés et les afficher
        offeredTrips.forEach(trip => {
            const tripCard = document.createElement('div');
            tripCard.classList.add('trip-card');
            tripCard.innerHTML = `
                <p>Titre trajet : ${trip.title}</p>
                <p>Départ : ${trip.departure}</p>
                <p>Arrivée : ${trip.arrival}</p>
                <p>Date : ${new Date(trip.date).toLocaleDateString()}</p>
                <p>Heure de départ : ${trip.departureTime}</p>
                <p>Heure d'arrivée : ${trip.arrivalTime}</p>
                <p>Places libres : ${trip.seats}</p>
                <p>Prix en crédit : ${trip.price} </p>
                <input type="button" class="launch-trip-btn" value="Lancer le trajet">
                <input type="button" class="end-trip-btn" value="Terminer">
                <input type="button" class="edit-trip-btn" value="Modifier">
                <input type="button" class="cancel-trip-btn" value="Annuler">
            `;
            offeredTripsContainer.appendChild(tripCard);

            // Gérer les écouteurs d'événements
            const launchTripBtn = tripCard.querySelector('.launch-trip-btn');
            const endTripBtn = tripCard.querySelector('.end-trip-btn');
            const editTripBtn = tripCard.querySelector('.edit-trip-btn');
            const cancelTripBtn = tripCard.querySelector('.cancel-trip-btn');

            // Écouteur pour "Lancer le trajet"
            launchTripBtn.addEventListener('click', async () => {
                try {
                    const response = await fetch(`/api/trips/${trip.id}/launch`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    });
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP! statut: ${response.status}`);
                    }
                    console.log('Trajet lancé avec succès!');
                } catch (error) {
                    console.error('Erreur lors du lancement du trajet:', error);
                }
            });

            // Écouteur pour "Terminer le trajet"
            endTripBtn.addEventListener('click', async () => {
                try {
                    const response = await fetch(`/api/trips/${trip.id}/end`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    });
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP! statut: ${response.status}`);
                    }
                    console.log('Trajet terminé avec succès!');
                } catch (error) {
                    console.error('Erreur lors de la terminaison du trajet:', error);
                }
            });

            // Écouteur pour "Modifier le trajet"
            editTripBtn.addEventListener('click', async () => {
                // On crée un formulaire pour modifier le trajet
                const updateForm = document.createElement('div');
                updateForm.innerHTML = `
                    <h3>Modifier le trajet</h3>
                    <label for="new-title-${trip.id}">Titre:</label>
                    <input type="text" id="new-title-${trip.id}" value="${trip.title}">
                    <label for="new-departure-${trip.id}">Départ:</label>
                    <input type="text" id="new-departure-${trip.id}" value="${trip.departure}">
                    <label for="new-arrival-${trip.id}">Arrivée:</label>
                    <input type="text" id="new-arrival-${trip.id}" value="${trip.arrival}">
                    <label for="new-date-${trip.id}">Date:</label>
                    <input type="date" id="new-date-${trip.id}" value="${trip.date.split('T')[0]}">
                    <label for="new-departure-time-${trip.id}">Heure de départ:</label>
                    <input type="time" id="new-departure-time-${trip.id}" value="${trip.departureTime}">
                    <label for="new-arrival-time-${trip.id}">Heure d'arrivée:</label>
                    <input type="time" id="new-arrival-time-${trip.id}" value="${trip.arrivalTime}">
                    <label for="new-seats-${trip.id}">Places:</label>
                    <input type="number" id="new-seats-${trip.id}" value="${trip.seats}">
                    <label for="new-price-${trip.id}">Prix en crédit:</label>
                    <input type="number" id="new-price-${trip.id}" value="${trip.price}">
                    <button class="submit-update-btn">Soumettre</button>
                    <button class="cancel-update-btn">Annuler</button>
                `;
                tripCard.appendChild(updateForm);

                // On attache l'écouteur au bouton Soumettre du NOUVEAU formulaire
                const submitUpdateBtn = updateForm.querySelector('.submit-update-btn');
                submitUpdateBtn.addEventListener('click', async () => {
                    // Recuperation des nouvelles valeurs
                    const newTripData = {
                        title: updateForm.querySelector(`#new-title-${trip.id}`).value,
                        departure: updateForm.querySelector(`#new-departure-${trip.id}`).value,
                        arrival: updateForm.querySelector(`#new-arrival-${trip.id}`).value,
                        date: updateForm.querySelector(`#new-date-${trip.id}`).value,
                        departureTime: updateForm.querySelector(`#new-departure-time-${trip.id}`).value,
                        arrivalTime: updateForm.querySelector(`#new-arrival-time-${trip.id}`).value,
                        seats: parseInt(updateForm.querySelector(`#new-seats-${trip.id}`).value, 10),
                        price: parseFloat(updateForm.querySelector(`#new-price-${trip.id}`).value)
                    };

                    try {
                        const response = await fetch(`/api/trips/${trip.id}/update`, {
                            method: 'POST',
                            headers: {'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json'},
                            body: JSON.stringify(newTripData)
                        });

                        if (!response.ok) {
                            throw new Error(`Erreur HTTP! statut: ${response.status}`);
                        }
                        console.log('Trajet modifié avec succès!');
                        // Logique pour masquer le formulaire et rafraîchir la carte
                    } catch (error) {
                        console.error('Erreur lors de la modification du trajet:', error);
                    }
                });
            });

            // Écouteur pour "Annuler le trajet"
            cancelTripBtn.addEventListener('click', async () => {
                try {
                    const response = await fetch(`/api/trips/${trip.id}/delete`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                    });
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP! statut: ${response.status}`);
                    }
                    console.log('Trajet annulé avec succès!');
                } catch (error) {
                    console.error('Erreur lors de l\'annulation du trajet:', error);
                }
            });
        });
    } catch (error) {
        console.error('Erreur lors de la récupération des covoiturages proposés:', error);
    }
}