const detailsBtn = document.querySelectorAll(".details-btn");

// Ajout de l'écouteur d'événement pour le bouton détails

export function viewDetails() {
    // il va parcourir tous les boutons avec la classe "details-btn"
    detailsBtn.forEach(btn => {
        btn.addEventListener("click", function () {
            // au clic redirige vers la page de détails qui contient les informations du conducteur
            window.open("../html/details.html", "_self");
        });
    });
}

viewDetails()