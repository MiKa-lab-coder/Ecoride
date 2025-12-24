export async function manageAdminActions() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Token non trouvé dans le localStorage.');
        return;
    }
    // Recuperer le rôle de l'utilisateur
    const userRole = localStorage.getItem('userRole');
    if (userRole !== '1') {
        return;
    }
    
    const form = document.getElementById('manage-account-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Obtient le bouton qui a déclenché l'événement. Utiliser e.submitter
            const submit = e.submitter;
            if (!submit) return;

            let path = '';
            const userActionData = {};

            // Détermine l'action à partir de la classe du bouton cliqué
            if (submit.classList.contains('create-user-btn')) {
                path = '/api/admin/create-account';
                // Récupère toutes les données nécessaires pour la création
                userActionData.username = form.username.value;
                userActionData.email = form.email.value;
                userActionData.password = form.password.value;
                userActionData.role_id = form.role_id.value;

            } else if (submit.classList.contains('suspend-user-btn')) {
                path = '/api/admin/suspend-user';
                // Récupère seulement le nom d'utilisateur et l'email pour la suspension
                userActionData.username = form.username.value;
                userActionData.email = form.email.value;

            } else if (submit.classList.contains('restore-user-btn')) {
                path = '/api/admin/reactivate-user';
                // Récupère seulement le nom d'utilisateur et l'email pour la réactivation
                userActionData.username = form.username.value;
                userActionData.email = form.email.value;

            } else if (submit.classList.contains('update-user-btn')) {
                path = '/api/admin/change-role';
                // Récupère seulement le nom d'utilisateur, l'email et le rôle pour la mise à jour
                userActionData.username = form.username.value;
                userActionData.email = form.email.value;
                userActionData.role_id = form.role_id.value;
            } else {
                console.error('Action non reconnue.');
                return;
            }
            // Envoi de la requête appropriée au serveur
            try {
                const response = await fetch(path, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(userActionData)
                });

                const result = await response.json();

                if (response.ok) {
                    alert('Action réussie: ' + (result.message || 'Opération effectuée.'));
                    form.reset();
                    // Rafraîchir la liste des utilisateurs
                    await displayUserList(token);
                } else {
                    alert('Échec de l\'action: ' + (result.message || 'Erreur inconnue.'));
                }
            } catch (error) {
                console.error('Erreur de requête:', error);
                alert('Erreur technique.');
            }
        });
    }
    
    // Afficher la liste des utilisateurs au chargement
    await displayUserList(token);
}

async function displayUserList(token) {
    const tbody = document.getElementById('user-list-tbody');
    if (!tbody) return;
    
    try {
        const response = await fetch('/api/admin/users', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        
        if (!response.ok) throw new Error('Erreur lors de la récupération des comptes.');
        
        const result = await response.json();
        const users = result.data; // Accès à result.data
        
        tbody.innerHTML = '';
        
        if (!users || users.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">Aucun compte n\'est disponible.</td></tr>';
            return;
        }
        
        users.forEach(user => {
            const row = document.createElement('tr');
            // Mapping des rôles pour l'affichage
            let roleName = 'Utilisateur';
            if (user.role_id === 1) roleName = 'Admin';
            if (user.role_id === 2) roleName = 'Modérateur';

            row.innerHTML = `
                <td>${user.user_id}</td>
                <td>${user.username}</td>
                <td>${user.email}</td>
                <td>${user.account_status}</td>
                <td>${roleName}</td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error('Erreur lors de la récupération des comptes:', error);
        tbody.innerHTML = '<tr><td colspan="5">Erreur lors du chargement des données.</td></tr>';
    }
}