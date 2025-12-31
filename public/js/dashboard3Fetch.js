/**
 * Récupère et affiche les évaluations reçues par l'utilisateur.
 */
export async function fetchReceivedReviews() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Aucun token trouvé pour afficher les évaluations.');
        return;
    }

    // Récupération du conteneur des cartes
    const container = document.getElementById('reviews-container');
    if (!container) {
        console.warn('Le conteneur d\'évaluations "reviews-container" est introuvable.');
        return;
    }

    try {
        const response = await fetch('/api/reviews/received', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des évaluations.');
        }

        const reviews = await response.json();
        
        container.innerHTML = ''; // Vider le conteneur des cartes

        if (reviews.length === 0) {
            container.innerHTML = '<p>Vous n\'avez reçu aucune évaluation pour le moment.</p>';
        } else {
            reviews.forEach(review => {
                const reviewCard = document.createElement('div');
                reviewCard.className = 'trip-card';

                // Création des étoiles pour la note
                let stars = '';
                for (let i = 0; i < 5; i++) {
                    stars += i < review.rating ? '★' : '☆';
                }

                reviewCard.innerHTML = `
                    <h4>Trajet ${review.trip_departure} -> ${review.trip_arrival}</h4>
                    <p><strong>Date:</strong> ${review.trip_date}</p>
                    <p><strong>De:</strong> ${review.author_firstname} ${review.author_name}</p>
                    <p class="rating-stars">${stars}</p>
                    <p><em>"${review.comment}"</em></p>
                `;
                container.appendChild(reviewCard);
            });
        }

    } catch (error) {
        console.error('Erreur lors de l\'affichage des évaluations reçues:', error);
        container.innerHTML = '<p>Erreur lors du chargement des évaluations.</p>';
    }
}
