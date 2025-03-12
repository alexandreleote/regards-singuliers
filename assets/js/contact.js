document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour afficher/masquer les champs professionnels
    function toggleProfessionalFields() {
        const typeInputs = document.querySelectorAll('input[name="contact[type]"]');
        const professionalFields = document.querySelector('.professional-fields');
        
        typeInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (this.value === 'professionnel' && this.checked) {
                    professionalFields.classList.add('visible');
                } else if (this.value === 'particulier' && this.checked) {
                    professionalFields.classList.remove('visible');
                }
            });
            
            // Initialiser l'affichage au chargement
            if (input.value === 'professionnel' && input.checked) {
                professionalFields.classList.add('visible');
            }
        });
    }
    
    toggleProfessionalFields();
});