//burger menu
export function setBurger(){
    const toggle = document.querySelector('.toggle');
    const navWrap = document.querySelector('.nav-wrap');

    if (toggle && navWrap) {
        toggle.addEventListener('click', () => {
            navWrap.classList.toggle('active');
            toggle.classList.toggle('active');
            const isActive = navWrap.classList.contains('active');
            toggle.setAttribute('aria-expanded', isActive);
        });
    }
}