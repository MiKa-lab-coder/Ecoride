// Affichage du profil utilisateur
export async function displayUserProfil() {
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

        // Nettoyer le conteneur avant d'ajouter de nouveaux éléments
        profileContainer.innerHTML = '';

        // Créer et ajouter les éléments du profil utilisateur
        const h2 = document.createElement('h2');
        h2.textContent = 'Profil Utilisateur';
        profileContainer.appendChild(h2);

        const pName = document.createElement('p');
        pName.innerHTML = `<strong>Nom:</strong> ${userData.name}`;
        profileContainer.appendChild(pName);

        const pFirstname = document.createElement('p');
        pFirstname.innerHTML = `<strong>Prenom:</strong> ${userData.firstname}`;
        profileContainer.appendChild(pFirstname);

        const pEmail = document.createElement('p');
        pEmail.innerHTML = `<strong>Email:</strong> ${userData.email}`;
        profileContainer.appendChild(pEmail);

        const pRating = document.createElement('p');
        pRating.innerHTML = `<strong>Ma note :</strong> ${averageRating} ⭐`;
        profileContainer.appendChild(pRating);

        const pCredit = document.createElement('p');
        pCredit.innerHTML = `<strong>Solde de crédits :</strong> ${creditBalance} Crédits`;
        profileContainer.appendChild(pCredit);

        const editButton = document.createElement('input');
        editButton.type = 'button';
        editButton.className = 'edit-profile';
        editButton.value = 'Modifier';
        profileContainer.appendChild(editButton);

        editButton.addEventListener('click', () => {
            // Nettoyer le conteneur avant d'ajouter le formulaire
            profileContainer.innerHTML = '';

            const editFormTitle = document.createElement('h2');
            editFormTitle.textContent = 'Modifier le Profil';
            profileContainer.appendChild(editFormTitle);

            const editForm = document.createElement('form');
            editForm.id = 'edit-profile-form';

            const fields = [
                { id: 'username', label: 'Username:', value: userData.username, type: 'text', required: true },
                { id: 'name', label: 'Nom:', value: userData.name, type: 'text', required: true },
                { id: 'firstname', label: 'Prenom:', value: userData.firstname, type: 'text', required: true },
                { id: 'email', label: 'Email:', value: userData.email, type: 'email', required: true }
            ];

            fields.forEach(field => {
                const label = document.createElement('label');
                label.textContent = field.label;
                label.htmlFor = field.id;

                const input = document.createElement('input');
                input.type = field.type;
                input.id = field.id;
                input.name = field.id;
                input.value = field.value;
                if (field.required) {
                    input.required = true;
                }

                editForm.appendChild(label);
                editForm.appendChild(input);
                editForm.appendChild(document.createElement('br'));
            });

            const submitButton = document.createElement('button');
            submitButton.type = 'submit';
            submitButton.textContent = 'Enregistrer';
            editForm.appendChild(submitButton);

            const cancelButton = document.createElement('button');
            cancelButton.type = 'button';
            cancelButton.id = 'cancel-edit';
            cancelButton.textContent = 'Annuler';
            editForm.appendChild(cancelButton);

            profileContainer.appendChild(editForm);

            cancelButton.addEventListener('click', () => {
                displayUserProfil();
            });

            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                const updatedData = {
                    username: editForm.querySelector('#username').value,
                    name: editForm.querySelector('#name').value,
                    firstname: editForm.querySelector('#firstname').value,
                    email: editForm.querySelector('#email').value,
                };

                try {
                    const updateResponse = await fetch(`/api/user/profile/${userData.id}/update`, {
                        method: 'POST',
                        headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                        body: JSON.stringify(updatedData)
                    });

                    if (!updateResponse.ok) {
                        throw new Error('Erreur lors de la mise à jour du profil.');
                    }
                    await displayUserProfil();
                } catch (updateError) {
                    console.error('Erreur lors de la mise à jour du profil:', updateError.message);
                }
            });
        });

    } catch (error) {
        console.error('Erreur lors du chargement du profil:', error.message);
    }
}