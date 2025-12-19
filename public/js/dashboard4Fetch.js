/**
 * Affiche les véhicules de l'utilisateur et gère leur suppression.
 */
export async function displayUserCar() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Aucun token trouvé pour afficher les véhicules.');
        return;
    }

    const myCarsContainer = document.querySelector('.my-cars');
    const carsContainer = document.getElementById('cars-container');

    if (!myCarsContainer || !carsContainer) {
        console.warn('Un des conteneurs de véhicules est introuvable.');
        return;
    }

    try {
        const response = await fetch('/api/vehicles/user', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des véhicules.');
        }

        const vehicles = await response.json();
        
        // Vider le conteneur des cartes de véhicules avant de le remplir
        carsContainer.innerHTML = ''; 

        if (vehicles.length === 0) {
            carsContainer.innerHTML = '<p>Aucun véhicule enregistré.</p>';
        } else {
            vehicles.forEach(vehicle => {
                const carCard = document.createElement('div');
                carCard.className = 'car-card';
                carCard.innerHTML = `
                    <h4>${vehicle.brand} ${vehicle.model}</h4>
                    <p>Immatriculation: ${vehicle.registration_number}</p>
                    <p>Année: ${new Date(vehicle.first_service).getFullYear()}</p>
                    <p>Nombre de places: ${vehicle.seating_capacity}</p>
                    <p>Couleur: ${vehicle.color}</p>
                    <p>Énergie: ${vehicle.energy_type}</p>
                    <button class="delete-car-btn" data-car-id="${vehicle.vehicle_id}">Supprimer</button>
                `;
                carsContainer.appendChild(carCard);
            });
        }

        // --- Création et gestion du bouton et formulaire d'ajout ---
        // Supprimer les anciens éléments s'ils existent pour éviter les doublons
        let addCarBtn = document.getElementById('add-car-btn');
        let addCarFormContainer = document.querySelector('.add-car-form');

        if (addCarBtn) addCarBtn.remove();
        if (addCarFormContainer) addCarFormContainer.remove();

        // Créer le bouton "Ajouter"
        addCarBtn = document.createElement('button');
        addCarBtn.id = 'add-car-btn';
        addCarBtn.className = 'add-car-btn';
        addCarBtn.textContent = 'Ajouter';
        myCarsContainer.appendChild(addCarBtn);

        // Créer le conteneur du formulaire d'ajout
        addCarFormContainer = document.createElement('div');
        addCarFormContainer.className = 'add-car-form js-hidden';
        addCarFormContainer.innerHTML = `
            <form id="add-car-form-js" method="post" action="">
                <label for="registration_number">Plaque d'immatriculation</label>
                <input type="text" id="registration_number" name="registration_number" required>

                <label for="first_service">Date de première mise en circulation</label>
                <input type="date" id="first_service" name="first_service" required>

                <label for="brand">Marque</label>
                <input type="text" id="brand" name="brand" required>

                <label for="model">Modèle</label>
                <input type="text" id="model" name="model" required>

                <label for="color">Couleur</label>
                <input type="text" id="color" name="color" required>

                <label for="seating_capacity">Nombre de places</label>
                <input type="number" id="seating_capacity" name="seating_capacity" required min="1" max="9">

                <label for="energy_type">Énergie</label>
                <select id="energy_type" name="energy_type" required>
                    <option value="combustion">Combustion</option>
                    <option value="electric">Électrique</option>
                    <option value="hybrid">Hybride</option>
                </select>

                <br>
                <br>
                <button class="create-car-btn" id="create-car-btn">Créer</button>
                <button type="button" class="cancel-car-btn" id="cancel-add-car-btn">Annuler</button>
            </form>
            <br>
        `;
        myCarsContainer.appendChild(addCarFormContainer);

        // Gérer la visibilité du bouton "Ajouter"
        if (vehicles.length >= 3) {
            addCarBtn.classList.add('js-hidden');
        } else {
            addCarBtn.classList.remove('js-hidden');
        }

        // Attacher les écouteurs d'événements aux éléments nouvellement créés
        setupAddNewCarListeners(addCarBtn, addCarFormContainer);


        // Attacher les écouteurs pour la suppression
        carsContainer.querySelectorAll('.delete-car-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                const carId = e.target.dataset.carId;
                if (confirm('Êtes-vous sûr de vouloir supprimer ce véhicule ?')) {
                    await deleteCar(carId, token);
                    await displayUserCar(); // Recharger la liste
                }
            });
        });

    } catch (error) {
        console.error('Erreur lors de l\'affichage des véhicules:', error);
        carsContainer.innerHTML = '<p>Erreur lors du chargement des véhicules.</p>';
    }
}

/**
 * Met en place l'écouteur d'événement pour le formulaire d'ajout de véhicule.
 * Cette fonction est maintenant interne et appelée par displayUserCar.
 */
function setupAddNewCarListeners(addCarBtn, addCarFormContainer) {
    const addCarForm = document.getElementById('add-car-form-js');
    const cancelCarBtn = document.getElementById('cancel-add-car-btn');

    if (addCarForm) {
        addCarForm.addEventListener('submit', addNewCar);
    }

    if (addCarBtn && addCarFormContainer && cancelCarBtn) {
        addCarBtn.addEventListener('click', () => {
            addCarFormContainer.classList.remove('js-hidden');
            addCarBtn.classList.add('js-hidden');
        });
        cancelCarBtn.addEventListener('click', () => {
            addCarFormContainer.classList.add('js-hidden');
            addCarBtn.classList.remove('js-hidden');
            addCarForm.reset(); // Réinitialiser le formulaire lors de l'annulation
        });
    }
}

/**
 * Gère la logique de suppression d'un véhicule.
 * @param {number} carId - L'ID du véhicule à supprimer.
 * @param {string} token - Le token JWT.
 */
async function deleteCar(carId, token) {
    try {
        const response = await fetch(`/api/vehicles/${carId}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}` }
        });
        if (!response.ok) {
            throw new Error('La suppression du véhicule a échoué.');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression du véhicule:', error);
        alert('Une erreur est survenue lors de la suppression.');
    }
}

/**
 * Gère la soumission du formulaire pour ajouter un nouveau véhicule.
 * @param {Event} event - L'événement de soumission du formulaire.
 */
async function addNewCar(event) {
    event.preventDefault();
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('Aucun token trouvé pour ajouter un véhicule.');
        return;
    }

    const form = event.target;
    const formData = new FormData(form);
    const carData = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('/api/vehicles', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(carData)
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Erreur lors de l\'ajout du véhicule.');
        }

        form.reset();
        document.querySelector('.add-car-form').classList.add('js-hidden');
        document.getElementById('add-car-btn').classList.remove('js-hidden');
        await displayUserCar(); // Recharger la liste des véhicules

    } catch (error) {
        console.error('Erreur lors de l\'ajout du véhicule:', error);
        alert(error.message);
    }
}