// Methode pour se déconnecter et supprimer le token du localStorage
export function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('userId');
    localStorage.removeItem('userRole');
    window.location.href = '/index.html'; // Redirige vers la page d'accueil
}

// Le gestionnaire d'événements qui appelle la fonction de déconnexion
document.addEventListener('DOMContentLoaded', () => {
    const logoutBtn = document.getElementById('logout-button');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Empêche le comportement par défaut du lien
            logout(); // Appelle la fonction de déconnexion
        });
    }
});