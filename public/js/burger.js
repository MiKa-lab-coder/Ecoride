//inclusion du burger menu
export async function loadBurger(url, elementId) {
    try {
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const content = await response.text();
        const target = document.getElementById(elementId);
        if (target) {
            const DivTemp = document.createElement('div');
            DivTemp.innerHTML = content;


            while (DivTemp.firstChild) {
                target.appendChild(DivTemp.firstChild);
            }
        } else {
            console.error(`Element ID : ${elementId} non trouvé.`);
        }
    } catch (error) {
        console.error(`impossible de charger : ${error.message}`);
    }
}
//logique pour le burger menu
export function setBurger(){
    const toggle = document.querySelector('.toggle');
    const wrapper = document.querySelector('.nav-wrapper');

    if (toggle && wrapper) {
        toggle.addEventListener('click', () => {
            wrapper.classList.toggle('active');
            const isActive = wrapper.classList.contains('active');
            toggle.setAttribute('aria-expanded', isActive);
        });
    }
}
//execution au chargement de la page
document.addEventListener('DOMContentLoaded',async () => {
    await loadBurger('/Ecoride/public/html/includes/header.html', 'header-placeholder');
    await loadBurger('/Ecoride/public/html/includes/footer.html', 'footer-placeholder');
    setBurger();
});