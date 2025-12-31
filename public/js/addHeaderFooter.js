/**
 * Chargement du header et du footer
 */
export async function loadHeadFoot(url, elementId) {
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