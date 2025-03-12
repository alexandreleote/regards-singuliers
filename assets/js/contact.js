document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.querySelector('form[name="contact"]');
    if (!contactForm) return;

    const typeInputs = contactForm.querySelectorAll('input[name="contact[type]"]');
    const entrepriseGroup = contactForm.querySelector('.form-group:has(input[name="contact[entreprise]"])');

    function toggleEntrepriseField() {
        const selectedType = contactForm.querySelector('input[name="contact[type]"]:checked').value;
        if (entrepriseGroup) {
            if (selectedType === 'professionnel') {
                entrepriseGroup.classList.remove('hidden');
                entrepriseGroup.querySelector('input').required = true;
            } else {
                entrepriseGroup.classList.add('hidden');
                entrepriseGroup.querySelector('input').required = false;
                entrepriseGroup.querySelector('input').value = '';
            }
        }
    }

    // Initial state
    toggleEntrepriseField();

    // Event listeners
    typeInputs.forEach(input => {
        input.addEventListener('change', toggleEntrepriseField);
    });

    // Phone number formatting
    const phoneInput = contactForm.querySelector('input[name="contact[telephone]"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,2}/g).join(' ').substr(0, 14);
            }
            e.target.value = value;
        });
    }
});
