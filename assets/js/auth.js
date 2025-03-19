// Import des styles
import '../styles/auth.css';
import './password-toggle.js';

// Code JavaScript pour l'authentification
console.log('Auth JS loaded');

function togglePasswordVisibility(icon) {
    const passwordField = icon.parentElement.querySelector('input');
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
} 