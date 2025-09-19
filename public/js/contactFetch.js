// On récupère le formulaire via le DOM
const contactForm = document.getElementById('contact-form');
const contactResponse = document.getElementById('contact-response');

// On ajoute un écouteur d'événement pour la soumission du formulaire
if (contactForm) {
    contactForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Empêche le rechargement de la page

        // On récupère les données du formulaire
        const formData = new FormData(contactForm);
        // On transforme les données en un objet simple
        const data = Object.fromEntries(formData.entries());

        // Pas de validation côté client puisqu'aucuns contact avec la base de données
        // On envoie transforme les données en JSON
        const jsonData = JSON.stringify(data);

        try {
            // On envoie les données au serveur pour le traitement par la methode de mail
            const response = await fetch('/api/contact/contact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: jsonData
            });

            // On traite la réponse du serveur
            if (response.ok) {
                // Si la réponse est OK, on affiche un message de succès
                contactResponse.innerHTML = '';
                contactResponse.textContent = 'Message envoyé avec succès !';
                contactForm.reset(); // On réinitialise le formulaire
            }
            else {
                const responseData = await response.json();
                contactResponse.innerHTML = '';
                contactResponse.textContent = "Un problème est survenu : " + (responseData.message || response.status);
            }
        } catch (error) {
            console.error('Erreur réseau ou autre :', error);
        }
    });
}