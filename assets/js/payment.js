export function initializePayment(publicKey, clientSecret) {
    const stripe = Stripe(publicKey);
    const elements = stripe.elements();

    // Style du formulaire de carte
    const style = {
        base: {
            color: '#1f2937',
            fontFamily: '"Segoe UI", system-ui, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#6b7280'
            }
        },
        invalid: {
            color: '#dc2626',
            iconColor: '#dc2626'
        }
    };

    // Création de l'élément carte
    const card = elements.create('card', {style: style});
    card.mount('#card-element');

    // Gestion des erreurs en temps réel
    card.addEventListener('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Soumission du formulaire
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        const submitButton = document.getElementById('submit-button');
        const spinner = document.getElementById('spinner');
        const buttonText = document.getElementById('button-text');

        // Désactiver le bouton et afficher le spinner
        submitButton.disabled = true;
        spinner.classList.remove('hidden');
        buttonText.textContent = 'Traitement en cours...';

        try {
            const result = await stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: card,
                }
            });

            if (result.error) {
                // Gérer les erreurs de paiement
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
                
                // Réactiver le bouton et cacher le spinner
                submitButton.disabled = false;
                spinner.classList.add('hidden');
                buttonText.textContent = 'Payer l\'acompte';
            } else {
                if (result.paymentIntent.status === 'succeeded') {
                    // Rediriger vers la page de confirmation
                    window.location.href = '/reservation/confirmation';
                }
            }
        } catch (error) {
            console.error('Erreur:', error);
            const errorElement = document.getElementById('card-errors');
            errorElement.textContent = 'Une erreur est survenue lors du traitement du paiement.';
            
            // Réactiver le bouton et cacher le spinner
            submitButton.disabled = false;
            spinner.classList.add('hidden');
            buttonText.textContent = 'Payer l\'acompte';
        }
    });
}
