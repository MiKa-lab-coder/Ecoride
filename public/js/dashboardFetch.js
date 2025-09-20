// Affichage du profil utilisateur
async function displayUserProfil() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('No token found in localStorage');
        return;
    }

    try {
        // Lancer la première requête pour les informations de base de l'utilisateur
        const userResponse = await fetch('/api/user/profile', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
        });

        if (!userResponse.ok) {
            throw new Error('Erreur lors de la récupération du profil utilisateur.');
        }

        const userData = await userResponse.json();
        const profileContainer = document.getElementById('profile-container');
        if (!profileContainer) {
            console.error('Profile container not found in the DOM');
            return;
        }

        // Lancer les deux requêtes en parallèle (note et crédits)
        const ratingPromise = fetch(`/api/user/${userData.id}/rating`, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
        });
        const creditPromise = fetch(`/api/transactions/${userData.id}/credits`, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
        });

        // Attendre la résolution des deux promesses
        const [ratingResponse, creditResponse] = await Promise.all([ratingPromise, creditPromise]);

        // Gérer les réponses individuelles
        const ratingData = ratingResponse.ok ? await ratingResponse.json() : { averageRating: null };
        const creditData = creditResponse.ok ? await creditResponse.json() : { creditBalance: 0 };

        // Formater les données pour l'affichage
        const averageRating = ratingData.averageRating ? parseFloat(ratingData.averageRating).toFixed(1) : 'N/A';
        const creditBalance = creditData.creditBalance !== undefined ? parseFloat(creditData.creditBalance).toFixed(0) : '0';

        // Mettre à jour le DOM en une seule fois
        profileContainer.innerHTML = `
            <h2>Profil Utilisateur</h2>
            <p><strong>Nom:</strong> ${userData.name}</p>
            <p><strong>Prenom:</strong> ${userData.firstname}</p>
            <p><strong>Email:</strong> ${userData.email}</p>
            <p><strong>Ma note :</strong> ${averageRating} ⭐</p>
            <p><strong>Solde de crédits :</strong> ${creditBalance} Crédits</p>
        `;

    } catch (error) {
        console.error('Erreur lors du chargement du profil:', error.message);
    }
}
