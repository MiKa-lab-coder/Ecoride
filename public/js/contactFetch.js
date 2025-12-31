/**
 * Formulaire de contact
 */
const contactForm = document.getElementById('contact-form');
const formResponse = document.getElementById('form-response');

// On ajoute un écouteur d'événement pour la soumission du formulaire
if (contactForm) {
    contactForm.addEventListener('submit', async (event) => {
        event.preventDefault(); // Empêche le rechargement de la page
        formResponse.innerHTML = '';
        formResponse.style.color = 'red';

        // Validation côté client
        const formData = new FormData(contactForm);
        const data = Object.fromEntries(formData.entries());
        const errors = [];

        if (!data.name.trim()) {
            errors.push("Le nom complet est requis.");
        }
        if (!data.subject.trim()) {
            errors.push("Le sujet est requis.");
        }
        if (!data.email.trim()) {
            errors.push("L'email est requis.");
        } else {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(data.email)) {
                errors.push("L'adresse email n'est pas valide.");
            }
        }
        if (!data.message.trim()) {
            errors.push("Le message est requis.");
        }

        // S'il y a des erreurs, on les affiche et on arrête l'exécution
        if (errors.length > 0) {
            const errorList = document.createElement('ul');
            errors.forEach(error => {
                const li = document.createElement('li');
                li.textContent = error;
                errorList.appendChild(li);
            });
            formResponse.appendChild(errorList);
            return; // On n'envoie pas le formulaire
        }
        // Fin de validation

        const jsonData = JSON.stringify(data);

        try {
            const response = await fetch('/api/contact/contact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: jsonData
            });

            const responseData = await response.json();
            formResponse.innerHTML = ''; // On vide à nouveau

            if (response.ok) {
                formResponse.textContent = responseData.message || 'Message envoyé avec succès !';
                formResponse.style.color = 'green';
                contactForm.reset(); // On réinitialise le formulaire
            } else {
                if (responseData.errors) {
                    const errorList = document.createElement('ul');
                    responseData.errors.forEach(error => {
                        const li = document.createElement('li');
                        li.textContent = error;
                        errorList.appendChild(li);
                    });
                    formResponse.appendChild(errorList);
                } else {
                    formResponse.textContent = "Un problème est survenu : " + (response.status);
                }
            }
        } catch (error) {
            console.error('Erreur réseau ou autre :', error);
            formResponse.innerHTML = '';
            formResponse.textContent = 'Une erreur de communication est survenue. Veuillez réessayer.';
        }
    });
}
