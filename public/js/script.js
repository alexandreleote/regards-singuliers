const stripe = Stripe('votre_cle_publique');
const elements = stripe.elements();

// Créer le formulaire de paiement
const form = document.getElementById('payment-form');
form.addEventListener('submit', async (event) => {
    event.preventDefault();
    
    const response = await fetch(`/api/create-payment-intent/${reservationId}`, {
        method: 'POST'
    });
    const data = await response.json();
    
    const result = await stripe.confirmPayment({
        clientSecret: data.clientSecret,
        elements,
        confirmParams: {
            return_url: `${window.location.origin}/payment/success`,
        },
    });
});