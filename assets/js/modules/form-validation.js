export function initFormValidation(formSelector = '.needs-validation') {
    'use strict';
    window.addEventListener('load', function() {
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