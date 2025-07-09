//burger menu
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