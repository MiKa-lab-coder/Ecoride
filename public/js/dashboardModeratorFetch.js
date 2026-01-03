/**
 * Récuperation des annonces en attente de validation par le modérateur
 */
export async function reviewOffer() {
    const reviewContainer = document.getElementById('manage-announcements-container');
    if (!reviewContainer) return;

    const token = localStorage.getItem('token');
    if (!token) {
        reviewContainer.innerHTML = '<p class="error">Vous devez être connecté pour accéder à cette page.</p>';
        return;
    }

    const userRole = localStorage.getItem('userRole');
    if (userRole !== '2' && userRole !== '1') {
        reviewContainer.innerHTML = '<p class="error">Vous n\'avez pas les droits pour accéder à cette page.</p>';
        return;
    }

    try {
        const response = await fetch('/api/admin/pending-trips', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!response.ok) throw new Error('Erreur lors de la récupération des offres.');

        const responseData = await response.json();
        const trips = responseData.data; // Accéder au tableau de données

        if (!trips || trips.length === 0) {
            reviewContainer.innerHTML = '<p>Aucune offre en attente de validation.</p>';
            return;
        }

        // Vider le conteneur avant d'afficher les nouvelles cartes
        reviewContainer.innerHTML = '';

        // Boucle pour afficher les offres sous forme de cartes
        trips.forEach(trip => {
            const tripCard = document.createElement('div');
            tripCard.className = 'trip-card';
            tripCard.innerHTML = `
                <h4>Trajet ${trip.departure_location} -> ${trip.arrival_location}</h4>
                <p><strong>ID Trajet:</strong> ${trip.trip_id}</p>
                <p><strong>Email Conducteur:</strong> ${trip.driver_email}</p>
                <p><strong>Date:</strong> ${new Date(trip.departure_day).toLocaleDateString()}</p>
                <p><strong>Prix:</strong> ${trip.trip_price} crédits</p>
                <button class="approve-btn" data-id="${trip.trip_id}">Approuver</button>
                <button class="reject-btn" data-id="${trip.trip_id}" data-email="${trip.driver_email}">Rejeter</button>
            `;
            reviewContainer.appendChild(tripCard);
        });

        // Utilisation de la délégation d'événements pour les boutons
        reviewContainer.addEventListener('click', async (e) => {
            const target = e.target;
            const tripId = target.getAttribute('data-id');

            if (!tripId) return;

            if (target.classList.contains('approve-btn')) {
                try {
                    const res = await fetch('/api/admin/approve-trip', {
                        method: 'POST',
                        headers: { 
                            'Authorization': `Bearer ${token}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ trip_id: tripId })
                    });
                    if (!res.ok) throw new Error('Erreur lors de l\'approbation de l\'offre.');
                    
                    const card = target.closest('.trip-card');
                    if (card) card.remove();

                } catch (error) {
                    console.error('Erreur:', error);
                }
            } else if (target.classList.contains('reject-btn')) {
                const driverEmail = target.getAttribute('data-email');
                if (confirm(`Êtes-vous sûr de vouloir rejeter ce trajet ? Un email sera envoyé à ${driverEmail}.`)) {
                    try {
                        const res = await fetch('/api/admin/reject-trip', {
                            method: 'POST',
                            headers: { 
                                'Authorization': `Bearer ${token}`,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ trip_id: tripId })
                        });
                        if (!res.ok) throw new Error('Erreur lors du rejet de l\'offre.');
                        

                        alert(`Trajet rejeté. Un email a été envoyé à ${driverEmail}.`);
                        
                        const card = target.closest('.trip-card');
                        if (card) card.remove();

                    } catch (error) {
                        console.error('Erreur:', error);
                    }
                }
            }

            // Si le conteneur devient vide, afficher un message
            if (reviewContainer.children.length === 0) {
                reviewContainer.innerHTML = '<p>Aucune offre en attente de validation.</p>';
            }
        });

    } catch (error) {
        console.error('Erreur:', error);
        reviewContainer.innerHTML = '<p class="error">Une erreur est survenue lors de la récupération des offres.</p>';
    }
}

/**
 * Récupération des litiges en attente de traitement par le modérateur
 */
export async function reviewReports() {
    const reportContainer = document.getElementById('report-container');
    if (!reportContainer) return;

    const token = localStorage.getItem('token');
    if (!token) {
        reportContainer.innerHTML = '<p class="error">Vous devez être connecté pour accéder à cette page.</p>';
        return;
    }

    const userRole = localStorage.getItem('userRole');
    if (userRole !== '2' && userRole !== '1') {
        reportContainer.innerHTML = '<p class="error">Vous n\'avez pas les droits pour accéder à cette page.</p>';
        return;
    }
    // Récupération des litiges
    try {
        const issues = await fetch('/api/admin/issues', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!issues.ok) throw new Error('Erreur lors de la récupération des litiges.');

        const issuesData = await issues.json();
        //console.log(issuesData);

        if (issuesData.length === 0) {
            reportContainer.innerHTML = '<p>Aucun litige en attente de traitement.</p>';
            return;
        }
        
        // Vider le conteneur avant d'afficher les nouvelles cartes
        reportContainer.innerHTML = '';

        // Boucle pour afficher les litiges sous forme de cartes
        issuesData.forEach(issue => {
            // On n'affiche que les litiges ouverts
            if (issue.status !== 'open') return;

            const issueCard = document.createElement('div');
            issueCard.className = 'issue-card';
            issueCard.classList.add('trip-card'); 
            
            issueCard.innerHTML = `
                <h4>Litige #${issue.id} - ${new Date(issue.created_at).toLocaleDateString()}</h4>
                <p><strong>Trajet :</strong> ${issue.trip_departure} -> ${issue.trip_arrival} (${new Date(issue.trip_date).toLocaleDateString()})</p>
                <hr>
                <p><strong>Plaignant :</strong> ${issue.plaintiff_username} (${issue.plaintiff_email})</p>
                <p><strong>Conducteur :</strong> ${issue.driver_username} (${issue.driver_email})</p>
                <hr>
                <p><strong>Description :</strong></p>
                <p><em>"${issue.description}"</em></p>
                <button class="resolve-btn" data-id="${issue.id}" style="background-color: #6b8e23; color: white; margin-top: 10px;">Clore le litige</button>
            `;
            reportContainer.appendChild(issueCard);
        });

        reportContainer.addEventListener('click', async (e) => {
            const target = e.target;
            const issueId = target.getAttribute('data-id');

            if (target.classList.contains('resolve-btn')) {
                if(confirm("Voulez-vous vraiment clore ce litige ?")) {
                    try {
                        const res = await fetch('/api/issues/close', {
                            method: 'POST',
                            headers: { 
                                'Authorization': `Bearer ${token}`,
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ issue_id: issueId })
                        });
                        if (!res.ok) throw new Error('Erreur lors de la clôture du litige.');
                        
                        const card = target.closest('.issue-card');
                        if (card) card.remove();
                        
                        alert("Litige clos avec succès.");
                    }
                    catch (error) {
                        console.error('Erreur:', error);
                        alert("Erreur lors de la clôture.");
                    }
                }
            }
            // Si le conteneur devient vide
            if (reportContainer.children.length === 0) {
                reportContainer.innerHTML = '<p>Aucun litige en attente de traitement.</p>';
            }
        });
    }
    catch (error) {
        console.error('Erreur:', error);
        reportContainer.innerHTML = '<p class="error">Une erreur est survenue lors de la récupération des litiges.</p>';
    }
}

/**
 * Récupération des évaluations pour validation par modérateur
 */
export async function fetchPendingReviews() {
    const ratingContainer = document.getElementById('rating-container');
    if (!ratingContainer) return;

    const token = localStorage.getItem('token');
    if (!token) {
        ratingContainer.innerHTML = '<p class="error">Vous devez être connecté pour accéder à cette page.</p>';
        return;
    }

    try {
        const response = await fetch('/api/reviews/pending', {
            method: 'GET',
            headers: {'Authorization': `Bearer ${token}`}
        });

        if (!response.ok) throw new Error('Erreur lors de la récupération des évaluations.');

        const reviews = await response.json();

        if (reviews.length === 0) {
            ratingContainer.innerHTML = '<p>Aucune évaluation en attente de validation.</p>';
            return;
        }

        ratingContainer.innerHTML = '';

        reviews.forEach(review => {
            const reviewCard = document.createElement('div');
            reviewCard.className = 'trip-card';
            reviewCard.innerHTML = `
                <h4>Évaluation du trajet ${review.trip_departure} -> ${review.trip_arrival}</h4>
                <p><strong>Auteur:</strong> ${review.author_name}</p>
                <p><strong>Note:</strong> ${review.rating} ★</p>
                <p><strong>Commentaire:</strong> <em>"${review.comment}"</em></p>
                <button class="approve-review-btn" data-id="${review.review_id}">Approuver</button>
                <button class="reject-review-btn" data-id="${review.review_id}">Rejeter</button>
            `;
            ratingContainer.appendChild(reviewCard);
        });


    } catch (error) {
        console.error('Erreur:', error);
        ratingContainer.innerHTML = '<p class="error">Une erreur est survenue lors de la récupération des évaluations.</p>';
    }

    // Utilisation de la délégation d'événements pour les boutons
    ratingContainer.addEventListener('click', async (e) => {
        const target = e.target;
        const reviewId = target.getAttribute('data-id');
        let newStatus = '';

        if (target.classList.contains('approve-review-btn')) {
            newStatus = 'approved';
        } else if (target.classList.contains('reject-review-btn')) {
            if (!confirm("Voulez-vous vraiment rejeter cette évaluation ?")) {
                return;
            }
            newStatus = 'rejected';
        }

        if (newStatus) {
            try {
                const res = await fetch('/api/reviews/update-status', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ review_id: reviewId, status: newStatus })
                });
                if (!res.ok) throw new Error('Erreur lors de la mise à jour de l\'évaluation.');

                const card = target.closest('.trip-card');
                if (card) card.remove();

                alert(`Évaluation ${newStatus === 'approved' ? 'approuvée' : 'rejetée'} avec succès.`);

            } catch (error) {
                //console.error('Erreur:', error);
                alert("Erreur lors de la mise à jour de l'évaluation.");
            }

            // Si le conteneur devient vide
            if (ratingContainer.children.length === 0) {
                ratingContainer.innerHTML = '<p>Aucune évaluation en attente de validation.</p>';
            }
        }
    });
}