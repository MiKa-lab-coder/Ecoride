//fonction pour afficher/masquer les éléments de la sidebar en fonction des rôles
export function displaySidebarByRoles(userRole) {
    const adminNav = document.querySelector('.admin-nav');
    const moderatorNav = document.querySelector('.moderator-nav');

    // Assure que les éléments de navigation existent avant de tenter de les masquer
    if (!adminNav || !moderatorNav) {
        console.error('Les éléments de navigation administrateur ou modérateur sont introuvables. Vérifiez la structure HTML.');
        return;
    }

    if (userRole !== 'admin') {
        adminNav.classList.add('js-hidden');
    }

    if (userRole !== 'admin' && userRole !== 'moderator') {
        moderatorNav.classList.add('js-hidden');
    }
}