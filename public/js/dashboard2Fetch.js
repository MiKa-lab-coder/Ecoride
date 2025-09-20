// Affichage du profil utilisateur
async function displayUserProfil() {
    const profileContainer = document.getElementById('profile-container');
    const token = localStorage.getItem('token');

    if (!profileContainer || !token) {
        console.error('Erreur: conteneur de profil ou token manquant.');
        return;
    }

    try {
        const userResponse = await fetch('/api/user/profile', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
        });

        if (!userResponse.ok) {
            throw new Error('Erreur lors de la récupération du profil utilisateur.');
        }

        const userData = await userResponse.json();

        const ratingPromise = fetch(`/api/user/${userData.id}/rating`, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
        });
        const creditPromise = fetch(`/api/transactions/${userData.id}/credits`, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
        });

        const [ratingResponse, creditResponse] = await Promise.all([ratingPromise, creditPromise]);

        const ratingData = ratingResponse.ok ? await ratingResponse.json() : { averageRating: null };
        const creditData = creditResponse.ok ? await creditResponse.json() : { creditBalance: 0 };

        const averageRating = ratingData.averageRating ? parseFloat(ratingData.averageRating).toFixed(1) : 'N/A';
        const creditBalance = creditData.creditBalance !== undefined ? parseFloat(creditData.creditBalance).toFixed(0) : '0';

        profileContainer.innerHTML = `
            <h2>Profil Utilisateur</h2>
            <p><strong>Nom:</strong> ${userData.name}</p>
            <p><strong>Prenom:</strong> ${userData.firstname}</p>
            <p><strong>Email:</strong> ${userData.email}</p>
            <p><strong>Ma note :</strong> ${averageRating} ⭐</p>
            <p><strong>Solde de crédits :</strong> ${creditBalance} Crédits</p>
            <input type="button" class="edit-profile" value="Modifier">
        `;

        const editButton = profileContainer.querySelector('.edit-profile');
        if (editButton) {
            editButton.addEventListener('click', () => {
                profileContainer.innerHTML = `
                    <h2>Modifier le Profil</h2>
                    <form id="edit-profile-form">
                        <label for="name">Nom:</label>
                        <input type="text" id="name" name="name" value="${userData.name}" required>
                        <br>
                        <label for="firstname">Prenom:</label>
                        <input type="text" id="firstname" name="firstname" value="${userData.firstname}" required>
                        <br>
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="${userData.email}" required>
                        <br>
                        <button type="submit">Enregistrer</button>
                        <button type="button" id="cancel-edit">Annuler</button>
                    </form>
                `;

                const cancelButton = document.getElementById('cancel-edit');
                if (cancelButton) {
                    cancelButton.addEventListener('click', () => {
                        displayUserProfil();
                    });
                }

                const editForm = document.getElementById('edit-profile-form');
                if (editForm) {
                    editForm.addEventListener('submit', async (e) => {
                        e.preventDefault();

                        const updatedName = document.getElementById('name').value;
                        const updatedFirstname = document.getElementById('firstname').value;
                        const updatedEmail = document.getElementById('email').value;

                        try {
                            const updateResponse = await fetch(`/api/user/profile/${userData.id}/update`, {
                                method: 'POST',
                                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    name: updatedName,
                                    firstname: updatedFirstname,
                                    email: updatedEmail
                                })
                            });

                            if (!updateResponse.ok) {
                                throw new Error('Erreur lors de la mise à jour du profil.');
                            }

                            await displayUserProfil();
                        } catch (updateError) {
                            console.error('Erreur lors de la mise à jour du profil:', updateError.message);
                        }
                    });
                }
            });
        }
    } catch (error) {
        console.error('Erreur lors du chargement du profil:', error.message);
    }
}