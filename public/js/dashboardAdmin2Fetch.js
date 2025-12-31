/**
 * Affichage des statistiques (Trajets)
 */
export async function statTrips() {
    // Récupérer le token d'authentification
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Token non trouvé dans le localStorage.');
        return;
    }
    // Récupérer le rôle de l'utilisateur
    const userRole = localStorage.getItem('userRole');
    if (userRole !== '1') {
        // console.error('Accès refusé. Rôle administrateur requis.');
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

        // Récupérer le canvas existant
        const canvas = document.getElementById('tripsChart');
        if (!canvas) {
            console.error('Canvas "tripsChart" non trouvé.');
            return;
        }

        // Détruire le graphique existant s'il y en a un pour éviter les superpositions
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        // Préparer les données pour Chart.js
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Nombre de trajets par jour',
                    data: stats.data,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Erreur lors de la récupération des statistiques des trajets:', error);
    }
}

/**
 * Affichage des statistiques (Crédits)
 */
export async function statCredit() {
    // Récupérer le token d'authentification
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Token non trouvé dans le localStorage.');
        return;
    }
    // Récupérer le rôle de l'utilisateur
    const userRole = localStorage.getItem('userRole');
    if (userRole !== '1') {
        return;
    }
    try {
        const response = await fetch('/api/admin/statCredits', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        const stats = await response.json();
        console.log('Statistiques des trajets:', stats);

        // Récupérer le canvas existant
        const canvas = document.getElementById('creditsChart');
        if (!canvas) {
            console.error('Canvas "creditsChart" non trouvé.');
            return;
        }

        // Détruire le graphique existant s'il y en a un
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }

        // Préparer les données pour Chart.js
        const ctx = canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line', // Graphique en ligne pour l'évolution des crédits
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Crédits gagnés (cumulés)',
                    data: stats.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    } catch (error) {
        console.error('Erreur lors de la récupération des statistiques des crédits:', error);
    }
}