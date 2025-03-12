document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('[data-controller="contact-type"]');
    const sections = document.querySelectorAll('[data-contact-type-target]');
    const commonFields = document.getElementById('common-fields');
    let activeSection = null;

    function updateFormSections() {
        const selectedType = typeSelect.value;
        sections.forEach(section => {
            if (section.dataset.contactTypeTarget === selectedType) {
                section.style.display = 'block';
                activeSection = section;
            } else {
                section.style.display = 'none';
            }
        });

        // Déplacer les champs communs dans la section active
        if (activeSection) {
            activeSection.insertBefore(commonFields, activeSection.firstChild);
        }

        // Mettre à jour le placeholder de l'email selon le type
        const emailInput = document.querySelector('#contact_email');
        if (emailInput) {
            emailInput.placeholder = selectedType === 'professionnel' ? 
                'exemple@entreprise.com' : 
                'exemple@email.com';
        }
    }

    typeSelect.addEventListener('change', updateFormSections);
    updateFormSections(); // État initial
});