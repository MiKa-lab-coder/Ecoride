export async function setupLoginFetch() {
// On récupère le formulaire et l'élément pour afficher les messages d'erreur
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');

    if (loginForm) {
        loginForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            // On efface les messages d'erreur
            errorMessage.textContent = '';

            const formData = new FormData(loginForm);

            // On convertit les données du formulaire en un objet simple
            const loginData = Object.fromEntries(formData.entries());

            // Validation des champs
            if (!loginData.username || !loginData.password) {
                errorMessage.textContent = 'Veuillez remplir tous les champs.';
                return;
            }

            try {
                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(loginData) // On envoie les données au format JSON
                });

                if (response.ok) {
                    const result = await response.json();
                    const token = result?.token;

                    if (!token) {
                        console.error('Erreur technique : Le token de connexion est manquant dans la réponse du serveur.');
                        // Redirection ou affichage d'un message générique pour l'utilisateur
                        errorMessage.textContent = 'Erreur lors de la connexion. Veuillez réessayer.';
                        return;
                    }

                    const decodedToken = JSON.parse(atob(token.split('.')[1]));
                    console.log('Decoded Token:', decodedToken);
                    const userId = decodedToken?.data?.id;
                    const userRole = decodedToken?.data?.role;

                    // Stockage du token et des informations utilisateur dans le localStorage
                    // pour l'accés aux pages protégées (dashboard, profil, etc.)
                    localStorage.setItem('token', token);
                    localStorage.setItem('userId', userId);
                    localStorage.setItem('userRole', userRole);

                    window.location.href = '/html/dashboard.html';

                } else {
                    const errorResult = await response.json();
                    errorMessage.textContent = errorResult?.message || "Nom d'utilisateur ou mot de passe incorrect.";
                }

            } catch (error) {
                console.error('Erreur de requête :', error);
                errorMessage.textContent = 'Impossible de se connecter au serveur. Veuillez vérifier votre connexion.';
            }
        });
    }
}
