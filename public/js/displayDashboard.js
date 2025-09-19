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
// Fonction pour afficher/masquer les éléments de la sidebar et du contenu
export function displayDashboardContentByRoles() {
    // 1. Définir les éléments de la page
    const adminNav = document.querySelector('.admin-nav');
    const moderatorNav = document.querySelector('.moderator-nav');
    const userNav = document.querySelector('.user-nav');
    const allSections = document.querySelectorAll('.dashboard-section');

    const token = localStorage.getItem('token');
    let userRole = "admin"; // Valeur par défaut pour les tests

    // 2. Vérifier l'existence et la validité du token
    if (token) {
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            userRole = payload.role;
        } catch (e) {
            console.error('Token invalide ou malformé', e);
        }
    }

    // 3. Masquer tout par défaut pour des raisons de sécurité
    if (adminNav) adminNav.classList.add('js-hidden');
    if (moderatorNav) moderatorNav.classList.add('js-hidden');
    if (userNav) userNav.classList.add('js-hidden');

    // Masquer toutes les sections du tableau de bord
    allSections.forEach(section => section.classList.add('js-hidden'));

    // 4. Afficher le contenu en fonction du rôle de l'utilisateur
    if (userRole === 'admin') {
        // L'administrateur voit tout
        if (adminNav) adminNav.classList.remove('js-hidden');
        if (moderatorNav) moderatorNav.classList.remove('js-hidden');
        if (userNav) userNav.classList.remove('js-hidden');
        allSections.forEach(section => section.classList.remove('js-hidden'));
    } else if (userRole === 'moderator') {
        // Le modérateur voit ses sections et celles de l'utilisateur
        if (moderatorNav) moderatorNav.classList.remove('js-hidden');
        if (userNav) userNav.classList.remove('js-hidden');
        document.getElementById('review-content-content').classList.remove('js-hidden');
        document.getElementById('user-reports-content').classList.remove('js-hidden');
        document.getElementById('view-profile-content').classList.remove('js-hidden');
        document.getElementById('manage-trips-content').classList.remove('js-hidden');
        document.getElementById('review-content').classList.remove('js-hidden');
        document.getElementById('past-trips-content').classList.remove('js-hidden');
    } else if (userRole === 'user') {
        // L'utilisateur simple ne voit que ses sections
        if (userNav) userNav.classList.remove('js-hidden');
        document.getElementById('view-profile-content').classList.remove('js-hidden');
        document.getElementById('manage-trips-content').classList.remove('js-hidden');
        document.getElementById('review-content').classList.remove('js-hidden');
        document.getElementById('past-trips-content').classList.remove('js-hidden');
    } else {
        // Si pas de rôle valide, tout reste masqué et on affiche un message
        const mainContent = document.querySelector('main');
        if (mainContent) {
            mainContent.innerHTML = '<p>Accès refusé. Veuillez vous connecter.</p>';
        }
    }
}