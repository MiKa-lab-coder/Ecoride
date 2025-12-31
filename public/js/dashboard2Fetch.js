/**
 * Affiche le profil de l'utilisateur et gère sa modification.
 */
export async function displayUserProfil() {
    const profileContainer = document.getElementById('profile-container');
    const profilePicContainer = document.getElementById('profile-pic-container');
    const token = localStorage.getItem('token');

    if (!profileContainer || !profilePicContainer || !token) {
        console.error('Erreur: Un des conteneurs principaux ou le token est manquant.');
        return;
    }

    try {
        const response = await fetch('/api/user/profile', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' }
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la récupération du profil utilisateur.');
        }

        const userData = await response.json();

        // Vider les conteneurs avant de les remplir
        profilePicContainer.innerHTML = '';
        profileContainer.innerHTML = '';

        // Création et gestion de la photo de profil
        setupProfilePicture(profilePicContainer, userData.photo);

        // Affichage des informations textuelles
        const averageRating = userData.driver_rating ? parseFloat(userData.driver_rating).toFixed(1) : 'N/A';
        const creditBalance = userData.credit !== null ? parseFloat(userData.credit).toFixed(0) : '0';

        profileContainer.innerHTML = `
            <h2>Mes informations</h2>
            <p><strong>Nom:</strong> ${userData.name}</p>
            <p><strong>Prénom:</strong> ${userData.firstname}</p>
            <p><strong>Email:</strong> ${userData.email}</p>
            <p><strong>Ma note :</strong> ${averageRating} ⭐</p>
            <p><strong>Solde de crédits :</strong> ${creditBalance} Crédits</p>
            <input type="button" class="edit-profile" value="Modifier">
        `;

        // Gestion de l'édition des informations
        profileContainer.querySelector('.edit-profile').addEventListener('click', () => {
            displayEditForm(profileContainer, userData);
        });

    } catch (error) {
        console.error('Erreur lors du chargement du profil:', error.message);
        profileContainer.innerHTML = '<p>Impossible de charger le profil. Veuillez réessayer.</p>';
    }
}

/**
 * Affiche le formulaire de modification du profil.
 */
function displayEditForm(container, userData) {
    container.innerHTML = `
        <h2>Modifier le Profil</h2>
        <form id="edit-profile-form">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="${userData.username}" required><br>
            
            <label for="name">Nom:</label>
            <input type="text" id="name" name="name" value="${userData.name}" required><br>
            
            <label for="firstname">Prénom:</label>
            <input type="text" id="firstname" name="firstname" value="${userData.firstname}" required><br>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="${userData.email}" required><br>
            
            <button type="submit">Enregistrer</button>
            <button type="button" id="cancel-edit">Annuler</button>
        </form>
    `;

    container.querySelector('#cancel-edit').addEventListener('click', () => {
        displayUserProfil();
    });

    container.querySelector('#edit-profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const updatedData = {
            username: form.querySelector('#username').value,
            name: form.querySelector('#name').value,
            firstname: form.querySelector('#firstname').value,
            email: form.querySelector('#email').value,
        };

        try {
            const token = localStorage.getItem('token');
            const updateResponse = await fetch(`/api/user/profile/${userData.id}/update`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}`, 'Content-Type': 'application/json' },
                body: JSON.stringify(updatedData)
            });

            if (!updateResponse.ok) {
                throw new Error('Erreur lors de la mise à jour du profil.');
            }
            await displayUserProfil(); // Recharger le profil
        } catch (updateError) {
            console.error('Erreur lors de la mise à jour du profil:', updateError.message);
        }
    });
}

/**
 * Gère la modification de la photo de profil.
 */
function setupProfilePicture(container, photoUrl) {
    // Création des éléments
    const profilePicImg = document.createElement('img');
    profilePicImg.src = photoUrl || '/uploads/default.png';
    profilePicImg.alt = 'Photo de profil';

    const editButton = document.createElement('button');
    editButton.id = 'edit-pic-btn';
    editButton.className = 'edit-pic-btn';
    editButton.textContent = 'Modifier';

    const changePicForm = document.createElement('form');
    changePicForm.className = 'change-pic-form js-hidden';
    changePicForm.innerHTML = `
        <label for="profile-pic">Choisir une nouvelle photo :</label>
        <input type="file" id="profile-pic" name="photo" accept="image/*" required>
        <input type="submit" class="save-pic-btn" value="Enregistrer">
        <button type="button" class="cancel-pic-btn">Annuler</button>
    `;

    // Ajout des éléments au conteneur
    container.appendChild(profilePicImg);
    container.appendChild(editButton);
    container.appendChild(changePicForm);

    // Ajout des écouteurs d'événements
    editButton.addEventListener('click', () => {
        editButton.classList.add('js-hidden');
        profilePicImg.classList.add('js-hidden');
        changePicForm.classList.remove('js-hidden');
    });

    changePicForm.querySelector('.cancel-pic-btn').addEventListener('click', (e) => {
        e.preventDefault();
        editButton.classList.remove('js-hidden');
        profilePicImg.classList.remove('js-hidden');
        changePicForm.classList.add('js-hidden');
    });


    // Gestion de l'envoi du formulaire de modification de photo
    changePicForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = localStorage.getItem('token');
        if (!token) return;

        const formData = new FormData(changePicForm);

        try {
            const response = await fetch('/api/user/update-photo', {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${token}` },
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                // On met à jour l'image et on réinitialise le formulaire
                profilePicImg.src = result.newProfilePicUrl + '?t=' + new Date().getTime(); // Anti-cache
                changePicForm.reset();
                editButton.classList.remove('js-hidden');
                profilePicImg.classList.remove('js-hidden');
                changePicForm.classList.add('js-hidden');
            } else {
                const error = await response.json();
                alert('Erreur lors de la mise à jour: ' + error.message);
            }
        } catch (error) {
            console.error('Erreur réseau lors de la mise à jour:', error);
        }
    });
}