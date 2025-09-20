// Afficher et modifier photos de profil
export async function setupProfilePicture() {
    // Récupération des éléments du DOM
    const profilePicContainer = document.getElementById('profile-pic-container');
    const profilePicImg = profilePicContainer?.querySelector('img');
    const editButton = document.getElementById('edit-pic-btn');
    const changePicForm = document.querySelector('.change-pic-form');
    const cancelButton = document.getElementById('cancel-pic-btn');

    // Si un élément crucial manque, on arrête.
    if (!profilePicContainer || !profilePicImg || !editButton || !changePicForm || !cancelButton) {
        console.error('Certains éléments de la photo de profil sont manquants.');
        return;
    }

    // Écouteurs pour les boutons "Modifier" et "Annuler"
    editButton.addEventListener('click', () => {
        // Affiche le formulaire et cache les autres éléments
        editButton.classList.add('js-hidden');
        changePicForm.classList.remove('js-hidden');
        profilePicImg.classList.add('js-hidden');
    });

    cancelButton.addEventListener('click', (e) => {
        e.preventDefault();
        // Cache le formulaire et affiche les autres éléments
        editButton.classList.remove('js-hidden');
        changePicForm.classList.add('js-hidden');
        profilePicImg.classList.remove('js-hidden');
    });

    // Chargement de la photo de profil actuelle
    const token = localStorage.getItem('token');
    if (token) {
        try {
            const response = await fetch('/api/user/photo', {
                method: 'GET',
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const userData = await response.json();
            const profilePicUrl = userData?.profilePictureUrl;

            profilePicImg.src = profilePicUrl || '../img/default-profile.png';
            profilePicImg.alt = profilePicUrl ? 'Photo de profil' : 'Photo de profil par défaut';
        } catch (error) {
            console.error('Erreur lors du chargement de la photo de profil :', error);
            profilePicImg.src = '../img/default-profile.png';
        }
    }

    // Ajout de l'écouteur pour la soumission du formulaire
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
                console.log('Photo de profil mise à jour avec succès');
                // On met à jour l'image en ajoutant un paramètre pour éviter le cache
                profilePicImg.src = result.newProfilePicUrl + '?t=' + new Date().getTime();
                profilePicImg.alt = 'Photo de profil';
                // On réinitialise et cache le formulaire
                changePicForm.reset();
                editButton.classList.remove('js-hidden');
                changePicForm.classList.add('js-hidden');
                profilePicImg.classList.remove('js-hidden');
            } else {
                const error = await response.json();
                alert('Erreur lors de la mise à jour: ' + error.message);
            }
        } catch (error) {
            console.error('Erreur réseau lors de la mise à jour:', error);
        }
    });
}