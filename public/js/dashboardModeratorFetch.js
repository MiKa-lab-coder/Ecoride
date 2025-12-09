// Revue du contenu
export async function reviewOffer() {
    const reviewContainer = document.getElementById('review-container');
    if (!reviewContainer) return;

    const token = localStorage.getItem('token');
    if (!token) {
        reviewContainer.innerHTML = '<p class="error">Vous devez être connecté pour accéder à cette page.</p>';
        return;
    }

    const userRole = localStorage.getItem('role');
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

        const trips = await response.json();
        console.log(trips);

        if (trips.length === 0) {
            reviewContainer.innerHTML = '<p>Aucune offre en attente de validation.</p>';
            return;
        }

        // Utilisation de slice() pour afficher au maximum 10 offres
        const displayedTrips = trips.slice(0, 10);

        // Vider le conteneur avant d'afficher les nouvelles cartes
        reviewContainer.innerHTML = '';

        // Boucle pour afficher les offres sous forme de cartes
        displayedTrips.forEach(trip => {
            const tripCard = document.createElement('div');
            tripCard.className = 'trip-card';
            tripCard.innerHTML = `
                <h3>Trajet ID: ${trip.id}</h3>
                <p>Trajet: ${trip.title}</p>
                <p>Chauffeur ID: ${trip.driver_id}</p>
                <p>Date: ${trip.departure_day}</p>
                <p>Prix: ${trip.price} €</p>
                <button class="approve-btn" data-id="${trip.id}">Approuver</button>
                <button class="reject-btn" data-id="${trip.id}">Rejeter</button>
            `;
            reviewContainer.appendChild(tripCard);
        });

        // Utilisation de la délégation d'événements pour les boutons
        reviewContainer.addEventListener('click', async (e) => {
            const target = e.target;
            const tripId = target.getAttribute('data-id');

            if (target.classList.contains('approve-btn')) {
                try {
                    const res = await fetch(`/api/admin/approve-trip/${tripId}`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    if (!res.ok) throw new Error('Erreur lors de l\'approbation de l\'offre.');
                    const card = target.closest('.trip-card');
                    if (card) card.remove();
                } catch (error) {
                    console.error('Erreur:', error);
                }
            } else if (target.classList.contains('reject-btn')) {
                try {
                    const res = await fetch(`/api/admin/reject-trip/${tripId}`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    if (!res.ok) throw new Error('Erreur lors du rejet de l\'offre.');
                    const card = target.closest('.trip-card');
                    if (card) card.remove();
                } catch (error) {
                    console.error('Erreur:', error);
                }
            }

            // Si le conteneur devient vide, on peut recharger la page
            if (reviewContainer.children.length === 0) {
                // Option : recharger après un délai pour une meilleure UX
                setTimeout(() => window.location.reload(), 1000);
            }
        });

    } catch (error) {
        console.error('Erreur:', error);
        reviewContainer.innerHTML = '<p class="error">Une erreur est survenue lors de la récupération des offres.</p>';
    }
}
// Revue des litiges
export async function reviewReports() {
    const reportContainer = document.getElementById('report-container');
    if (!reportContainer) return;

    const token = localStorage.getItem('token');
    if (!token) {
        reportContainer.innerHTML = '<p class="error">Vous devez être connecté pour accéder à cette page.</p>';
        return;
    }

    const userRole = localStorage.getItem('role');
    if (userRole !== 'Moderator' && userRole !== 'Admin') {
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
        console.log(issuesData);

        if (issuesData.length === 0) {
            reportContainer.innerHTML = '<p>Aucun litige en attente de traitement.</p>';
            return;
        }
        // Utilisation de slice() pour afficher au maximum 10 litiges
        const displayedIssues = issuesData.slice(0, 10);

        // Vider le conteneur avant d'afficher les nouvelles cartes
        reportContainer.innerHTML = '';

        // Boucle pour afficher les litiges sous forme de cartes
        displayedIssues.forEach(issue => {
            const issueCard = document.createElement('div');
            issueCard.className = 'issue-card';
            issueCard.innerHTML = `
                <h3>Litige ID: ${issue.id}</h3>
                <p>Trajet ID: ${issue.trip_id}</p>
                <p>Conducteur ID:${issue.driver_id}</p>
                <p>Utilisateur ID: ${issue.user_id}</p>
                <p>Description: ${issue.description}</p>
                <button class="resolve-btn" data-id="${issue.id}">Clore</button>
            `;
            reportContainer.appendChild(issueCard);
        });

        // Utilisation de la délégation d'événements pour les boutons
        reportContainer.addEventListener('click', async (e) => {
            const target = e.target;
            const issueId = target.getAttribute('data-id');

            if (target.classList.contains('resolve-btn')) {
                try {
                    const res = await fetch(`/api/issues/close/${issueId}`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    if (!res.ok) throw new Error('Erreur lors de la clôture du litige.');
                    const card = target.closest('.issue-card');
                    if (card) card.remove();
                }
                catch (error) {
                    console.error('Erreur:', error);
                }
            }
            // Si le conteneur devient vide, on peut recharger la page
            if (reportContainer.children.length === 0) {
                // Option : recharger après un délai pour une meilleure UX
                setTimeout(() => window.location.reload(), 1000);
            }
        });
    }
    catch (error) {
        console.error('Erreur:', error);
        reportContainer.innerHTML = '<p class="error">Une erreur est survenue lors de la récupération des litiges.</p>';
    }
}