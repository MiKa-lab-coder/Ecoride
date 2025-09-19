// Fonction utilitaire pour décoder un token JWT
export function parseJwt(token) {
    try {
        if (!token) {
            console.warn('Token JWT non fourni.');
            return null;
        }
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    } catch (e) {
        console.error('Erreur lors du parsing du token JWT:', e);
        return null;
    }
}