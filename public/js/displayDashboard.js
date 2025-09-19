// Permet d'afficher le lien de navigation du tableau dans le menu si l'utilisateur est connecté
export async function createDashboardLink() {
    const token = localStorage.getItem('token');
    const dashboardLink = document.querySelector('.nav-links');

    if (token && dashboardLink) {
        const listItem = document.createElement('li');
        const link = document.createElement('a');
        link.href = '/html/dashboard.html';
        link.textContent = 'dashboard';
        listItem.appendChild(link);
        dashboardLink.appendChild(listItem);
    }
}
//fonction pour afficher/masquer les éléments de la sidebar en fonction des rôles
export function displaySidebarByRoles(userRole) {
    const adminNav = document.querySelector('.admin-nav');
    const moderatorNav = document.querySelector('.moderator-nav');
    const userNav = document.querySelector('.user-nav');
    const mainContent = document.querySelector('main');

    // Assure que les éléments de navigation existent avant de tenter de les masquer
    if (!adminNav || !moderatorNav || !userNav) {
        return;
    }
    // Recupérer le rôle de l'utilisateur à partir du token JWT
    const token = localStorage.getItem('token');
    if (token) {
        const payload = JSON.parse(atob(token.split('.')[1]));
        userRole = payload.role; // Supposant que le rôle est stocké dans le champ 'role'
    }
    // normalement un visiteur n'a pas accès au dashboard, mais on gère le cas où il y aurait un bug
        // pas de token, pas de rôle, pas de dashboard
    else {
        adminNav.classList.add('js-hidden');
        moderatorNav.classList.add('js-hidden');
        userNav.classList.add('js-hidden');
        if (mainContent) {
            mainContent.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <h2>Accès non autorisé</h2>
                    <p>Veuillez vous connecter pour accéder à cette page.</p>
                    <a href="/html/connexion.html">Aller à la page de connexion</a>
                </div>
            `;
        }
        return;
    }
    // Masquer les éléments de navigation en fonction du rôle de l'utilisateur
    // Remplacer par une vérification dynamique des rôles
    if (userRole !== 'admin') {
        adminNav.classList.add('js-hidden');
    }
    if (userRole !== 'admin' && userRole !== 'moderator') {
        moderatorNav.classList.add('js-hidden');
    }
}