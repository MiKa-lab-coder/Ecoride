export async function manageAdminActions() {
    const form = document.getElementById('manage-account-form');
    if (!form) {
        console.error('Formulaire non trouvé.');
        return;
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Obtient le bouton qui a déclenché l'événement. Utiliser e.submitter
        const submit = e.submitter;
        if (!submit) return;

        let path = '';
        const userActionData = {};

        // Détermine l'action à partir de la classe du bouton cliqué
        if (submit.classList.contains('create-user-btn')) {
            path = 'api/admin/create-account';
            // Récupère toutes les données nécessaires pour la création
            userActionData.username = form.username.value;
            userActionData.email = form.email.value;
            userActionData.password = form.password.value;
            userActionData.role = form.role.value;
            userActionData.status = form.status.value;

        } else if (submit.classList.contains('suspend-user-btn')) {
            path = 'api/admin/suspend-user';
            // Récupère seulement le nom d'utilisateur et l'email pour la suspension
            userActionData.username = form.username.value;
            userActionData.email = form.email.value;

        } else if (submit.classList.contains('restore-user-btn')) {
            path = 'api/admin/reactivate-user';
            // Récupère seulement le nom d'utilisateur et l'email pour la réactivation
            userActionData.username = form.username.value;
            userActionData.email = form.email.value;

        } else if (submit.classList.contains('update-user-btn')) {
            path = 'api/admin/change-role';
            // Récupère seulement le nom d'utilisateur, l'email et le rôle pour la mise à jour
            userActionData.username = form.username.value;
            userActionData.email = form.email.value;
            userActionData.role = form.role.value;
        } else {
            console.error('Action non reconnue.');
            return;
        }
        // Envoi de la requête appropriée au serveur
        try {
            const response = await fetch(path, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(userActionData)
            });

            const result = await response.json();
            //console.log(result);

            if (response.ok) {
                console.log('Action réussie:', result);
                form.reset();
            } else {
                console.error('Échec de l\'action:', result.message || 'Erreur inconnue.');
            }
        } catch (error) {
            console.error('Erreur de requête:', error);
        }
    });
}