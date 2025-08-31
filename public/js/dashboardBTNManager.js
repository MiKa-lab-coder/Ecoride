// Sélection des boutons d'affichage
const changePicDisplayBtn = document.getElementById('edit-pic-btn');
const editProfileDisplayBtn = document.getElementById('edit-profile-btn');
const addCarsDisplayBtn = document.getElementById('add-car-btn');
const addTripDisplayBtn = document.getElementById('add-trips-btn');

// Sélection des boutons d'annulation
const cancelPicBtn = document.getElementById('cancel-pic-btn');
const cancelEditBtn = document.getElementById('cancel-edit-btn');
const cancelAddCarBtn = document.getElementById('cancel-add-car-btn');
const cancelAddTripBtn = document.getElementById('cancel-trip-btn');

// Sélection des blocs à afficher
const changePicForm = document.querySelector('.change-pic-form');
const editProfileForm = document.querySelector('.edit-profile');
const addCarForm = document.querySelector('.add-car-form');
const addTripForm = document.querySelector('.add-trip-form');

// Fonction pour attacher un écouteur de bascule
const setupToggleListener = (showBtn, cancelBtn, container) => {
    if (showBtn && cancelBtn && container) {
        showBtn.addEventListener('click', (event) => {
            event.preventDefault();
            container.classList.remove('js-hidden');
            showBtn.classList.add('js-hidden');
        });

        cancelBtn.addEventListener('click', (event) => {
            event.preventDefault();
            container.classList.add('js-hidden');
            showBtn.classList.remove('js-hidden');
        });
    } else {
        console.error('Un ou plusieurs éléments sont introuvables. Vérifiez les sélecteurs.');
    }
};

// Regroupement des appels de fonctions
export function initDashboardButtons() {
    setupToggleListener(changePicDisplayBtn, cancelPicBtn, changePicForm);
    setupToggleListener(editProfileDisplayBtn, cancelEditBtn, editProfileForm);
    setupToggleListener(addCarsDisplayBtn, cancelAddCarBtn, addCarForm);
    setupToggleListener(addTripDisplayBtn, cancelAddTripBtn, addTripForm);
}





