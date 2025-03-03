export function initFormValidation(formSelector = '.needs-validation') {
    window.addEventListener('load', function() {
        'use strict';
        const forms = document.querySelectorAll(formSelector);
        
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
}