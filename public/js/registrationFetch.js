// Récupérer les données du formulaire d'inscription
const registrationData = document.getElementById('registration-form');

if (registrationData) {
    registrationData.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Créer un objet FormData pour envoyer les données du formulaire
        const formData = new FormData(registrationData);

        // On récupère les valeurs des champs
        const name = formData.get('name');
        const firstname = formData.get('firstname');
        const birthdate = formData.get('birthdate');
        const username = formData.get('username');
        const photo = formData.get('photo');
        const email = formData.get('email');
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');

        // On prépare un tableau pour stocker les messages d'erreur
        const errorMessages = [];

        // On valide les données et push les messages dans le tableau si besoin
        if (!name || name.length < 2) errorMessages.push("Le nom doit contenir au moins 2 caractères.");
        if (!firstname || firstname.length < 2) errorMessages.push("Le prénom doit contenir au moins 2 caractères.");
        if (!birthdate) errorMessages.push("La date de naissance est requise.");
        if (!username || username.length < 4) errorMessages.push("Le nom d'utilisateur doit contenir au moins 4 caractères.");
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errorMessages.push("L'adresse e-mail n'est pas valide.");
        if (!password || password.length < 8) errorMessages.push("Le mot de passe doit contenir au moins 8 caractères.");
        if (password !== confirmPassword) errorMessages.push("Les mots de passe ne correspondent pas.");
        if (photo && photo.size > 3 * 1024 * 1024) errorMessages.push("La taille de la photo ne doit pas dépasser 3 Mo.");

        // Récupérer le div 'helper' pour afficher les messages d'aide
        const helper = document.getElementById('helper');
        // On vide le contenu du div 'helper' pour ne pas accumuler les messages
        helper.innerHTML = '';

        // On affiche les messages d'erreur s'il y en a en parcourant le tableau
        if (errorMessages.length > 0) {
            errorMessages.forEach(msg => {
                const p = document.createElement("p");
                p.textContent = msg;
                helper.appendChild(p);
            });
        } else {
            try {
                // Envoyer les données du formulaire au serveur via fetch
                const response = await fetch('/api/auth/registration', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    // Si l'inscription est réussie, redirection vers la page de connexion
                    window.location.href = '/html/connexion.html';
                } else {
                    // Si le serveur renvoie une erreur, on l'affiche dans le helper
                    const result = await response.json();
                    const p = document.createElement("p");
                    p.textContent = result.message || "Erreur de connexion au serveur.";
                    helper.appendChild(p);
                }

            } catch (error) {
                console.error('Erreur inscription:', error);
                const errorMessage = document.createElement("p");
                errorMessage.textContent = "Une erreur est survenue. Veuillez réessayer plus tard.";
                helper.appendChild(errorMessage);
            }
        }
    });
}