// Affichage des vehicules de l'utilisateur (max 3)
export async function displayUserCar() {
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('No token found in localStorage');
        return;
    }
    const carContainer = document.getElementById('cars-container');
    const addCarForm = document.getElementById('add-car-form'); // Assure-toi d'avoir cet ID sur ton formulaire

    if (!carContainer || !addCarForm) {
        console.error('No required DOM elements found');
        return;
    }

    try {
        const response = await fetch('/api/vehicles/user', {
            method: 'GET',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des véhicules');
        }

        const vehicles = await response.json();
        console.log(vehicles);

        // C'est ici que tu gères la limite des 3 véhicules
        if (vehicles.length >= 3) {
            carContainer.innerHTML = '<p>Vous avez atteint le nombre maximum de véhicules enregistrés (3).</p>';
            addCarForm.style.display = 'none'; // Cache le formulaire d'ajout
            return;
        } else {
            addCarForm.style.display = 'block'; // S'assure que le formulaire est visible si la limite n'est pas atteinte
        }

        if (vehicles.length === 0) {
            carContainer.innerHTML = '<p>Aucun véhicule enregistré.</p>';
        } else {
            // on boucle sur les véhicules et on crée des cartes pour chaque véhicule
            carContainer.innerHTML = '';
            vehicles.forEach((vehicle) => {
                const carCard = document.createElement('div');
                carCard.classList.add('car-card');
                carCard.innerHTML = `
                    <h4>Marque: ${vehicle.brand}</h4>
                    <p>Modèle: ${vehicle.model}</p>       
                    <p>Année: ${vehicle.year}</p>
                    <p>Immatriculation: ${vehicle.registration_number}</p>
                    <p>Couleur: ${vehicle.color}</p>
                    <p>Énergie:${vehicle.energy_type}</p>
                    <button class="delete-btn" data-car-id="${vehicle.id}">Supprimer</button>
                `;
                carContainer.appendChild(carCard);
            });
        }

        // Ajouter des écouteurs d'événements aux boutons de suppression
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach((button) => {
            button.addEventListener('click', async (e) => {
                const carId = e.target.dataset.carId;
                const response = await fetch(`/api/vehicles/${carId}`, {
                    method: 'DELETE',
                    headers: {
                        Authorization: `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                });
                if (!response.ok) {
                    throw new Error('Erreur lors de la suppression du véhicule');
                }
                const carCard = e.target.closest('.car-card');
                if (carCard) {
                    carCard.remove();
                }
                // Réafficher les véhicules pour mettre à jour l'état
                await displayUserCar();
            });
        });
    } catch (error) {
        console.error('Error fetching vehicles:', error);
        carContainer.innerHTML = '<p>Erreur lors de la récupération des véhicules.</p>';
    }
}
// Ajouter un nouveau véhicule
export async function addNewCar(event) {
    // Empecher le rechargement de la page
    event.preventDefault();
    // Récupérer le token depuis le localStorage
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('No token found in localStorage');
        return;
    }

    // Vérifier si la limite de 3 véhicules est atteinte
    try {
        const checkResponse = await fetch('/api/vehicles', {
            method: 'GET',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
        });
        const existingVehicles = await checkResponse.json();

        if (existingVehicles.length >= 3) {
            alert('Vous ne pouvez pas ajouter plus de 3 véhicules.');
            console.warn('Limite de véhicules atteinte.');
            return;
        }
    } catch (error) {
        console.error('Erreur lors de la vérification des véhicules existants:', error);
        return;
    }

    // Si la limite n'est pas atteinte, on procède à l'ajout
    const brand = document.getElementById('brand').value;
    const model = document.getElementById('model').value;
    const year = document.getElementById('year').value;
    const registration_number = document.getElementById('registration_number').value;
    const color = document.getElementById('color').value;
    const energy_type = document.getElementById('energy_type').value;

    if (!brand || !model || !year || !registration_number || !color || !energy_type) {
        console.log('Veuillez remplir tous les champs.');
        return;
    }

    const carData = { brand, model, year, registration_number, color, energy_type };

    try {
        const response = await fetch('/api/vehicles', {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(carData),
        });

        if (!response.ok) {
            throw new Error('Erreur lors de l\'ajout du véhicule');
        }

        document.getElementById('add-car-form').reset();
        await displayUserCar(); // On rafraîchit l'affichage

    } catch (error) {
        console.error('Error adding vehicle:', error);
    }
}