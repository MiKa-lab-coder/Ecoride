/**
 * Affiche les trajets auxquels l'utilisateur est inscrit.
 */
export async function fetchBooking() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Aucun token trouvé pour afficher les réservations.');
        return;
    }

    const bookingsContainer = document.getElementById('bookings-container');
    if (!bookingsContainer) {
        console.warn('Le conteneur de réservations "bookings-container" est introuvable.');
        return;
    }

    try {
        const response = await fetch('/api/bookings/user', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des réservations.');
        }

        const bookings = await response.json();
        
        bookingsContainer.innerHTML = '';

        if (bookings.length === 0) {
            bookingsContainer.innerHTML = '<p>Vous n\'êtes inscrit à aucun trajet pour le moment.</p>';
        } else {
            const title = document.createElement('h3');
            title.textContent = 'Mes réservations';
            bookingsContainer.appendChild(title);

            bookings.forEach(booking => {
                const bookingCard = document.createElement('div');
                bookingCard.className = 'booking-card';
                bookingCard.innerHTML = `
                    <h4>Trajet vers ${booking.arrival_location}</h4>
                    <p><strong>Départ:</strong> ${booking.departure_location}</p>
                    <p><strong>Date:</strong> ${new Date(booking.departure_day).toLocaleDateString()}</p>
                    <p><strong>Heure:</strong> ${booking.departure_time}</p>
                    <p><strong>Conducteur:</strong> ${booking.driver_firstname} ${booking.driver_name}</p>
                    <p><strong>Prix:</strong> ${booking.trip_price} crédits</p>
                    <button class="cancel-booking-btn" data-booking-id="${booking.booking_id}">Annuler ma participation</button>
                `;
                bookingsContainer.appendChild(bookingCard);
            });
        }
        // Attacher les écouteurs d'événements
        bookingsContainer.querySelectorAll('.cancel-booking-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const bookingId = e.target.dataset.bookingId;
                if (confirm('Êtes-vous sûr de vouloir annuler votre participation à ce trajet ?')) {
                    await cancelBooking(bookingId, token);
                    await fetchBooking();
                }
            });
        });

    } catch (error) {
        console.error('Erreur lors de l\'affichage des réservations:', error);
        bookingsContainer.innerHTML = '<p>Erreur lors du chargement des réservations.</p>';
    }
}

/**
 * Annule une réservation.
 */
async function cancelBooking(bookingId, token) {
    try {
        const response = await fetch('/api/bookings/cancel', {
            method: 'DELETE', // Changé en DELETE pour correspondre à la route
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ booking_id: bookingId })
        });
        if (!response.ok) {
            throw new Error('L\'annulation de la réservation a échoué.');
        }
        // Mettre à jour la page après l'annulation
        window.location.reload();
    } catch (error) {
        console.error('Erreur lors de l\'annulation:', error);
        alert('Une erreur est survenue lors de l\'annulation.');
    }
}

/**
 * Affiche les trajets proposés par l'utilisateur (conducteur).
 */
export async function fetchOfferedTrip() {
    const token = localStorage.getItem('token');
    if (!token) return;

    const driverContent = document.getElementById('drivers-content');
    if (!driverContent) return;

    const offeredTripsContainer = document.getElementById('offered-trips-cards-container');
    const addTripsBtn = document.getElementById('add-trips-btn');
    const addTripFormContainer = driverContent.querySelector('.add-trip-form-container');

    if (addTripFormContainer) {
        addTripFormContainer.classList.add('js-hidden');
    }

    if (addTripsBtn && addTripFormContainer) {
        addTripsBtn.addEventListener('click', () => {
            // Vérification si l'utilisateur a des véhicules
            const carsContainer = document.getElementById('cars-container');
            const hasCars = carsContainer && carsContainer.querySelectorAll('.car-card').length > 0;

            if (!hasCars) {
                alert("Vous ne pouvez pas proposer de trajet sans véhicule enregistré. Veuillez ajouter un véhicule dans la section 'Mon Profil'.");
                return;
            }

            addTripFormContainer.classList.toggle('js-hidden');
            if (!addTripFormContainer.classList.contains('js-hidden')) {
                renderTripForm(addTripFormContainer);
            }
        });
    }

    try {
        const response = await fetch('/api/trips/user', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!response.ok) throw new Error('Erreur lors de la récupération des trajets proposés.');

        const offeredTrips = await response.json();
        offeredTripsContainer.innerHTML = '';

        if (offeredTrips.length === 0) {
            offeredTripsContainer.innerHTML = '<p>Vous n\'avez proposé aucun trajet.</p>';
        } else {
            offeredTrips.forEach(trip => {
                const tripCard = document.createElement('div');
                tripCard.className = 'trip-card';
                tripCard.innerHTML = `
                    <h4>Trajet ${trip.departure_location} -> ${trip.arrival_location}</h4>
                    <p><strong>Date:</strong> ${new Date(trip.departure_day).toLocaleDateString()}</p>
                    <p><strong>Heure:</strong> ${trip.departure_time}</p>
                    <p><strong>Prix:</strong> ${trip.trip_price} crédits</p>
                    <p><strong>Places:</strong> ${trip.seating}</p>
                    <p><strong>Conducteur:</strong> ${trip.driver_firstname} ${trip.driver_name}</p>
                    <p><strong>Véhicule:</strong> ${trip.brand} ${trip.model} (${trip.registration_number})</p>
                    <p><strong>Animaux autorisés:</strong> ${trip.animal_pref ? 'Oui' : 'Non'}</p>
                    <p><strong>Fumeurs autorisés:</strong> ${trip.smoking_pref ? 'Oui' : 'Non'}</p>
                `;
                
                // Boutons d'action
                const actionsDiv = document.createElement('div');
                actionsDiv.className = 'trip-actions';

                if (trip.status === 'approved') {
                    actionsDiv.innerHTML += `<button class="edit-trip-btn" data-trip-id='${trip.trip_id}'>Modifier</button>`;
                    actionsDiv.innerHTML += `<button class="delete-trip-btn" data-trip-id='${trip.trip_id}'>Supprimer</button>`;
                    actionsDiv.innerHTML += `<button class="launch-trip-btn" data-trip-id='${trip.trip_id}'>Lancer</button>`;
                } else if (trip.status === 'ongoing') {
                    actionsDiv.innerHTML += `<button class="end-trip-btn" data-trip-id='${trip.trip_id}'>Terminer</button>`;
                } else if (trip.status === 'pending') {
                    actionsDiv.innerHTML += `<p>En attente de validation</p>`;
                    actionsDiv.innerHTML += `<button class="edit-trip-btn" data-trip-id='${trip.trip_id}'>Modifier</button>`;
                    actionsDiv.innerHTML += `<button class="delete-trip-btn" data-trip-id='${trip.trip_id}'>Supprimer</button>`;
                }

                tripCard.appendChild(actionsDiv);
                offeredTripsContainer.appendChild(tripCard);
            });
        }

        // Attacher les écouteurs d'événements
        offeredTripsContainer.querySelectorAll('.edit-trip-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const tripId = e.target.dataset.tripId;
                const tripData = offeredTrips.find(t => t.trip_id == tripId);
                if (addTripFormContainer) {
                    addTripFormContainer.classList.remove('js-hidden');
                    renderTripForm(addTripFormContainer, tripData);
                }
            });
        });

        offeredTripsContainer.querySelectorAll('.delete-trip-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const tripId = e.target.dataset.tripId;
                if (confirm('Êtes-vous sûr de vouloir supprimer ce trajet ?')) {
                    await deleteTrip(tripId, token);
                    await fetchOfferedTrip();
                }
            });
        });

        offeredTripsContainer.querySelectorAll('.launch-trip-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const tripId = e.target.dataset.tripId;
                if (confirm('Êtes-vous sûr de vouloir lancer ce trajet ? Cette action est irréversible.')) {
                    await launchTrip(tripId, token);
                    await fetchOfferedTrip();
                }
            });
        });

        offeredTripsContainer.querySelectorAll('.end-trip-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const tripId = e.target.dataset.tripId;
                if (confirm('Êtes-vous sûr de vouloir terminer ce trajet ?')) {
                    await endTrip(tripId, token);
                    await fetchOfferedTrip();
                }
            });
        });

    } catch (error) {
        console.error('Erreur lors de l\'affichage des trajets proposés:', error);
    }
}

/**
 * Affiche le formulaire de création ou de modification d'un trajet.
 */
async function renderTripForm(container, tripData = null) {
    const token = localStorage.getItem('token');
    const vehiclesResponse = await fetch('/api/vehicles/user', { headers: { 'Authorization': `Bearer ${token}` } });
    const vehicles = await vehiclesResponse.json();
    const vehicleOptions = vehicles.map(v => `<option value="${v.vehicle_id}" ${tripData && tripData.vehicle_id == v.vehicle_id ? 'selected' : ''}>${v.brand} ${v.model} (${v.registration_number})</option>`).join('');

    container.innerHTML = `
        <form id="propose-trip-form">
            <input type="hidden" name="trip_id" value="${tripData ? tripData.trip_id : ''}">
            
            <label for="departure_location">Départ :</label>
            <input type="text" name="departure_location" value="${tripData ? tripData.departure_location : ''}" required>

            <label for="arrival_location">Arrivée :</label>
            <input type="text" name="arrival_location" value="${tripData ? tripData.arrival_location : ''}" required>

            <label for="departure_day">Date de départ :</label>
            <input type="date" name="departure_day" max="9999-12-31" value="${tripData ? tripData.departure_day : ''}" required>
            
            <label for="departure_time">Heure de départ :</label>
            <input type="time" name="departure_time" value="${tripData ? tripData.departure_time : ''}" required>

            <label for="arrival_day">Date d'arrivée :</label>
            <input type="date" name="arrival_day" max="9999-12-31" value="${tripData ? tripData.arrival_day : ''}" required>

            <label for="arrival_time">Heure d'arrivée :</label>
            <input type="time" name="arrival_time" value="${tripData ? tripData.arrival_time : ''}" required>

            <label for="trip_price">Prix (crédits) :</label>
            <input type="number" name="trip_price" value="${tripData ? tripData.trip_price : ''}" required>

            <label for="seating">Places disponibles :</label>
            <input type="number" name="seating" value="${tripData ? tripData.seating : ''}" required>

            <label for="vehicle_id">Véhicule :</label>
            <select name="vehicle_id" required>${vehicleOptions}</select>

            <div>
                <input type="checkbox" name="smoking_pref" ${tripData && tripData.smoking_pref ? 'checked' : ''}>
                <label for="smoking_pref">Fumeurs autorisés</label>
            </div>
            <div>
                <input type="checkbox" name="animal_pref" ${tripData && tripData.animal_pref ? 'checked' : ''}>
                <label for="animal_pref">Animaux autorisés</label>
            </div>

            <button type="submit">${tripData ? 'Modifier' : 'Créer'}</button>
            <button type="button" class="cancel-form-btn">Annuler</button>
        </form>
    `;

    container.querySelector('.cancel-form-btn').addEventListener('click', () => {
        container.innerHTML = '';
        container.classList.add('js-hidden');
    });

    container.querySelector('#propose-trip-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        data.smoking_pref = form.querySelector('[name="smoking_pref"]').checked;
        data.animal_pref = form.querySelector('[name="animal_pref"]').checked;

        const tripId = data.trip_id;
        const method = 'POST';
        const url = tripId ? '/api/trips/update' : '/api/trips';

        try {
            const response = await fetch(url, {
                method: method,
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error(tripId ? 'Erreur lors de la modification.' : 'Erreur lors de la création.');
            
            await fetchOfferedTrip();
        } catch (error) {
            console.error(error);
        }
    });
}

async function deleteTrip(tripId, token) {
    try {
        const response = await fetch('/api/trips/delete', {
            method: 'DELETE',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ trip_id: tripId })
        });
        if (!response.ok) throw new Error('Erreur lors de la suppression.');
    } catch (error) {
        console.error(error);
    }
}

/**
 * Lance un trajet.
 */
async function launchTrip(tripId, token) {
    try {
        const response = await fetch(`/api/trips/start`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: tripId })
        });
        if (!response.ok) throw new Error('Erreur lors du lancement du trajet.');
    } catch (error) {
        console.error(error);
    }
}

async function endTrip(tripId, token) {
    try {
        const response = await fetch(`/api/trips/end`, {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
            body: JSON.stringify({ trip_id: tripId })
        });
        if (!response.ok) throw new Error('Erreur lors de la terminaison du trajet.');
    } catch (error) {
        console.error(error);
    }
}

/**
 * Affiche les trajets terminés et gère leur évaluation.
 */
export async function fetchPastTrips() {
    const token = localStorage.getItem('token');
    if (!token) return;

    const container = document.getElementById('past-trips-container');
    if (!container) return;

    try {
        const response = await fetch('/api/trips/past', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` },
        });
        if (!response.ok) throw new Error('Erreur lors de la récupération des trajets passés.');

        const trips = await response.json();
        //console.log('infos trajets reçus:', trips);
        container.innerHTML = '';

        if (trips.length === 0) {
            container.innerHTML = '<p>Aucun trajet précédent à afficher.</p>';
        } else {
            trips.forEach(trip => {
                const tripCard = document.createElement('div');
                tripCard.className = 'trip-card'; // Changé de 'past-trip-card' à 'trip-card' pour le style
                tripCard.innerHTML = `
                    <h4>Trajet vers ${trip.arrival_location}</h4>
                    <p><strong>Date:</strong> ${new Date(trip.departure_day).toLocaleDateString()}</p>
                    <p><strong>Avec:</strong> ${trip.user_to_rate_name}</p>
                    <div class="rating-form-container"></div>
                `;

                if (!trip.has_rated) {
                    const rateBtn = document.createElement('button');
                    rateBtn.textContent = 'Évaluer';
                    rateBtn.onclick = () => renderRatingForm(tripCard.querySelector('.rating-form-container'), trip, token);
                    tripCard.appendChild(rateBtn);
                } else {
                    tripCard.innerHTML += '<p><em>Évaluation déjà soumise.</em></p>';
                    // Bouton Signaler uniquement si l'évaluation est soumise
                    tripCard.innerHTML += `<button class="report-issue-btn" data-trip-id="${trip.trip_id}" style="background-color: #e74c3c; color: white; margin-top: 10px;">Signaler</button>`;
                }
                container.appendChild(tripCard);
            });

            // Gestionnaire pour les boutons "Signaler"
            container.querySelectorAll('.report-issue-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const tripId = e.target.dataset.tripId;
                    const description = prompt("Veuillez décrire le problème rencontré :");
                    
                    if (description) {
                        try {
                            const response = await fetch('/api/issues', {
                                method: 'POST',
                                headers: { 
                                    'Authorization': `Bearer ${token}`,
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ trip_id: tripId, description: description })
                            });

                            if (response.ok) {
                                alert("Votre signalement a bien été pris en compte.");
                            } else {
                                const errorData = await response.json();
                                alert("Erreur : " + (errorData.error || "Impossible de signaler le litige."));
                            }
                        } catch (error) {
                            console.error(error);
                            alert("Une erreur est survenue.");
                        }
                    }
                });
            });
        }
    } catch (error) {
        console.error('Erreur lors de l\'affichage des trajets passés:', error);
        container.innerHTML = '<p>Erreur lors du chargement des trajets.</p>';
    }
}

/**
 * Affiche le formulaire de notation.
 */
function renderRatingForm(container, trip, token) {
    container.innerHTML = `
        <form class="rating-form">
            <select name="rating" required>
                <option value="">Note</option>
                <option value="1">1 ★</option>
                <option value="2">2 ★</option>
                <option value="3">3 ★</option>
                <option value="4">4 ★</option>
                <option value="5">5 ★</option>
            </select>
            <textarea name="comment" placeholder="Votre commentaire..."></textarea>
            <button type="submit">Envoyer</button>
        </form>
    `;

    container.querySelector('.rating-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = {
            trip_id: trip.trip_id,
            rated_user_id: trip.user_to_rate_id,
            rating: formData.get('rating'),
            comment: formData.get('comment')
        };

        try {
            const response = await fetch('/api/ratings', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error('Erreur lors de la soumission de l\'évaluation.');
            
            await fetchPastTrips();
        } catch (error) {
            console.error(error);
            alert(error.message);
        }
    });
}