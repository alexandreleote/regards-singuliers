import '../../css/reservation/payment.css';

document.addEventListener('DOMContentLoaded', function() {
    const stripe = Stripe(document.querySelector('[data-stripe-key]').dataset.stripeKey);
    const clientSecret = document.querySelector('[data-client-secret]').dataset.clientSecret;
    const elements = stripe.elements({
        clientSecret: clientSecret,
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: '#2563eb',
                colorBackground: '#ffffff',
                colorText: '#1f2937',
                colorDanger: '#dc2626',
                fontFamily: 'system-ui, -apple-system, sans-serif',
                spacingUnit: '4px',
                borderRadius: '8px'
            }
        }
    });

    const paymentElement = elements.create('payment');
    paymentElement.mount('#payment-element');

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit');
    const loadingElement = document.querySelector('.loading');
    const messageElement = document.getElementById('payment-message');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Désactiver le bouton et afficher le loader
        submitButton.disabled = true;
        loadingElement.classList.add('active');
        messageElement.textContent = '';

        try {
            const {error} = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: document.querySelector('[data-return-url]').dataset.returnUrl,
                }
            });

            if (error) {
                messageElement.textContent = error.message;
                submitButton.disabled = false;
                loadingElement.classList.remove('active');
            }
        } catch (e) {
            messageElement.textContent = "Une erreur est survenue lors du traitement du paiement.";
            submitButton.disabled = false;
            loadingElement.classList.remove('active');
        }
    });
}); 