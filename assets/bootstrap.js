import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp({
    debug: true,
    onError(error, controller) {
        console.error('Erreur Stimulus :', error, 'dans le contrôleur', controller);
    }
});

// Gestion globale des erreurs
window.addEventListener('error', (event) => {
    console.error('Erreur globale :', event.error);
});
