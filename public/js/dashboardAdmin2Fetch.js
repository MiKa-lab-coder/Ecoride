// Récupération et affichage des statistiques de la plateforme
// On utilisera Chart.js pour l'affichage des graphiques

export async function statTrips() {
    // Récupérer le token d'authentification
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Token non trouvé dans le localStorage.');
        return;
    }
    // Récupérer le rôle de l'utilisateur
    const userRole = localStorage.getItem('userRole');
    if (userRole !== 'admin') {
        console.error('Accès refusé. Rôle administrateur requis.');
        return;
    }
    try {
        const response = await fetch('/api/admin/statTrips', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        const stats = await response.json();
        console.log('Statistiques des trajets:', stats);

        // Récupérer le conteneur
        const statsContainer = document.getElementById('stat-trips-board');
        if (!statsContainer) {
            console.error('Conteneur des statistiques non trouvé.');
            return;
        }

        // Inserer un canvas pour le graphique
        const canvas = document.createElement('canvas');
        canvas.id = 'tripsChart';
        // Vider le conteneur et y insérer le nouveau canvas
        statsContainer.innerHTML = '';
        statsContainer.appendChild(canvas);

        // Préparer les données pour Chart.js
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Nombre de trajets hebdomadaires',
                    data: stats.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } catch (error) {
        console.error('Erreur lors de la récupération des statistiques des trajets:', error);
    }
}

export async function statCredit() {
    // Récupérer le token d'authentification
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Token non trouvé dans le localStorage.');
        return;
    }
    // Récupérer le rôle de l'utilisateur
    const userRole = localStorage.getItem('userRole');
    if (userRole !== 'admin') {
        console.error('Accès refusé. Rôle administrateur requis.');
        return;
    }
    try {
        const response = await fetch('api/admin/statCredits', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        const stats = await response.json();
        console.log('Statistiques des trajets:', stats);

        // Récupérer le conteneur
        const statsContainer = document.getElementById('stat-credits-board');
        if (!statsContainer) {
            console.error('Conteneur des statistiques non trouvé.');
            return;
        }

        // Inserer un canvas pour le graphique
        const canvas = document.createElement('canvas');
        canvas.id = 'creditsChart';
        // Vider le conteneur et y insérer le nouveau canvas
        statsContainer.innerHTML = '';
        statsContainer.appendChild(canvas);

        // Préparer les données pour Chart.js
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Nombre de credits hebdomadaires',
                    data: stats.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } catch (error) {
        console.error('Erreur lors de la récupération des statistiques des trajets:', error);
    }
}