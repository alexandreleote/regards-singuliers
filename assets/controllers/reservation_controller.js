import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'paymentElement', 'submitButton', 'loadingElement', 'messageElement']
    static values = {
        stripeKey: String,
        clientSecret: String,
        type: String // 'date', 'payment', 'success', 'canceled'
    }

    connect() {
        switch (this.typeValue) {
            case 'date':
                this.initializeDateSelection();
                break;
            case 'payment':
                this.initializePayment();
                break;
            case 'success':
                this.initializeSuccess();
                break;
            case 'canceled':
                this.initializeCanceled();
                break;
        }
    }

    // Gestion de la sélection de date
    initializeDateSelection() {
        if (this.hasFormTarget) {
            this.formTarget.addEventListener('submit', this.handleDateSubmit.bind(this));
        }
    }

    async handleDateSubmit(event) {
        event.preventDefault();
        const formData = new FormData(this.formTarget);
        
        try {
            const response = await fetch('/reservation/check-date', {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const data = await response.json();
                if (data.available) {
                    window.location.href = `/reservation/payment?date=${formData.get('date')}`;
                } else {
                    this.showMessage('Cette date n\'est pas disponible', 'error');
                }
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de la date:', error);
            this.showMessage('Une erreur est survenue', 'error');
        }
    }

    // Gestion du paiement
    initializePayment() {
        if (this.hasPaymentElementTarget) {
            this.initializeStripe();
        }
    }

    async initializeStripe() {
        const stripe = Stripe(this.stripeKeyValue);
        const elements = stripe.elements({
            clientSecret: this.clientSecretValue,
            appearance: {
                theme: 'stripe'
            }
        });

        const paymentElement = elements.create('payment');
        paymentElement.mount(this.paymentElementTarget);

        if (this.hasFormTarget) {
            this.formTarget.addEventListener('submit', async (event) => {
                event.preventDefault();
                this.submitButtonTarget.disabled = true;
                this.loadingElementTarget.classList.remove('hidden');

                try {
                    const { error } = await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                            return_url: `${window.location.origin}/reservation/success`,
                        },
                    });

                    if (error) {
                        this.showMessage(error.message, 'error');
                    }
                } catch (error) {
                    console.error('Erreur lors du paiement:', error);
                    this.showMessage('Une erreur est survenue', 'error');
                } finally {
                    this.submitButtonTarget.disabled = false;
                    this.loadingElementTarget.classList.add('hidden');
                }
            });
        }
    }

    // Gestion de la page de succès
    initializeSuccess() {
        // Animation ou logique spécifique à la page de succès
        setTimeout(() => {
            window.location.href = '/';
        }, 5000);
    }

    // Gestion de la page d'annulation
    initializeCanceled() {
        // Animation ou logique spécifique à la page d'annulation
        setTimeout(() => {
            window.location.href = '/';
        }, 5000);
    }

    // Utilitaires
    showMessage(message, type = 'info') {
        if (this.hasMessageElementTarget) {
            this.messageElementTarget.textContent = message;
            this.messageElementTarget.className = `message message-${type}`;
            this.messageElementTarget.classList.remove('hidden');
            
            setTimeout(() => {
                this.messageElementTarget.classList.add('hidden');
            }, 5000);
        }
    }

    disconnect() {
        if (this.hasFormTarget) {
            this.formTarget.removeEventListener('submit', this.handleDateSubmit.bind(this));
        }
    }
} 