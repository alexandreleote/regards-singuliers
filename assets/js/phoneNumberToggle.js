export function togglePhoneVisibility() {
    const showPhoneElement = document.getElementById('show-phone');
    const phoneNumberElement = document.getElementById('phone-number');

    if (showPhoneElement && phoneNumberElement) {
        // Masquer le texte "Afficher le numéro"
        showPhoneElement.classList.add('hidden');
        
        // Afficher le numéro de téléphone
        phoneNumberElement.classList.remove('hidden');
    }
}

// Ajout de l'écouteur d'événement une fois le DOM chargé
document.addEventListener('DOMContentLoaded', () => {
    const showPhoneElement = document.getElementById('show-phone');
    
    if (showPhoneElement) {
        showPhoneElement.addEventListener('click', togglePhoneVisibility);
        
        // Rendre explicitement le texte cliquable
        showPhoneElement.style.cursor = 'pointer';
    }
});