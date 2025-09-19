export async function setupRegistrationFetch() {

// Récupérer le formulaire d'inscription
    const registrationData = document.getElementById('registration-form');
    const helper = document.getElementById('helper');

    if (registrationData) {
        registrationData.addEventListener('submit', async (e) => {
            e.preventDefault();

            // On efface les messages d'erreur précédents
            helper.innerHTML = '';

            // On crée un objet FormData pour récupérer les données et les valider
            const formData = new FormData(registrationData);

            // On convertit les données du formulaire en un objet simple pour validation
            const data = Object.fromEntries(formData.entries());

            // On prépare un tableau pour stocker les messages d'erreur
            const errorMessages = [];

            // Validation des champs
            if (!data.name || data.name.length < 2) errorMessages.push("Le nom doit contenir au moins 2 caractères.");
            if (!data.firstname || data.firstname.length < 2) errorMessages.push("Le prénom doit contenir au moins 2 caractères.");
            if (!data.birthdate) errorMessages.push("La date de naissance est requise.");
            if (!data.username || data.username.length < 4) errorMessages.push("Le nom d'utilisateur doit contenir au moins 4 caractères.");
            if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) errorMessages.push("L'adresse e-mail n'est pas valide.");
            if (!data.password || data.password.length < 8) errorMessages.push("Le mot de passe doit contenir au moins 8 caractères.");
            if (data.password !== data.confirmPassword) errorMessages.push("Les mots de passe ne correspondent pas.");

            // On affiche les erreurs s'il y en a
            if (errorMessages.length > 0) {
                errorMessages.forEach(msg => {
                    const p = document.createElement("p");
                    p.textContent = msg;
                    helper.appendChild(p);
                });
                return; // On arrête l'exécution si la validation échoue
            }

            try {
                // Création d'un nouvel objet FormData pour l'envoi, incluant le fichier
                const sendFormData = new FormData();

                // On ajoute les champs de texte à l'objet FormData
                sendFormData.append('name', data.name);
                sendFormData.append('firstname', data.firstname);
                sendFormData.append('birthdate', data.birthdate);
                sendFormData.append('username', data.username);
                sendFormData.append('email', data.email);
                sendFormData.append('password', data.password);
                sendFormData.append('confirmPassword', data.confirmPassword);

                // On gère le fichier
                const photoInput = document.getElementById('photo');
                const photoFile = photoInput.files[0];

                if (photoFile) {
                    if (photoFile.size > 3 * 1024 * 1024) {
                        const p = document.createElement("p");
                        p.textContent = "La taille de la photo ne doit pas dépasser 3 Mo.";
                        helper.appendChild(p);
                        return;
                    }
                    sendFormData.append('photo', photoFile);
                }

                // Envoi des données au serveur via fetch
                const response = await fetch('/api/auth/registration', {
                    method: 'POST',
                    body: sendFormData
                });

                if (response.ok) {
                    // Si l'inscription est réussie
                    window.location.href = '/html/connexion.html';
                } else {
                    // Si le serveur renvoie une erreur
                    const result = await response.json();
                    const p = document.createElement("p");
                    p.textContent = result.message || "Une erreur est survenue lors de l'inscription.";
                    helper.appendChild(p);
                }
            } catch (error) {
                console.error('Erreur inscription:', error);
                const errorMessage = document.createElement("p");
                errorMessage.textContent = "Une erreur est survenue. Veuillez réessayer plus tard.";
                helper.appendChild(errorMessage);
            }
        });
    }
}