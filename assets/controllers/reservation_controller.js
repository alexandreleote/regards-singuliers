import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'paymentElement', 'submitButton', 'loadingElement', 'messageElement', 'container']
    static values = {
        stripeKey: String,
        clientSecret: String,
        type: String,
        calendlyUrl: String,
        serviceSlug: String,
        serviceId: String
    }

    connect() {
        if (!this.hasTypeValue) {
            console.error('Type value is missing');
            return;
        }

        if (this.typeValue === 'date') {
            if (!this.hasCalendlyUrlValue || !this.hasServiceSlugValue || !this.hasServiceIdValue) {
                console.error('Missing required values for date reservation', {
                    hasCalendlyUrl: this.hasCalendlyUrlValue,
                    hasServiceSlug: this.hasServiceSlugValue,
                    hasServiceId: this.hasServiceIdValue
                });
                return;
            }
            this.initializeCalendly();
        } else if (this.typeValue === 'payment') {
            if (!this.hasStripeKeyValue || !this.hasClientSecretValue) {
                console.error('Missing required values for payment');
                return;
            }
            this.initializePayment();
        }
    }

    initializeCalendly() {
        if (!document.getElementById('calendly-container')) {
            console.error('Calendly container not found');
            this.showMessage('Erreur de chargement du calendrier', 'error');
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://assets.calendly.com/assets/external/widget.js';
        script.async = true;

        script.onerror = () => {
            console.error('Failed to load Calendly script');
            this.showMessage('Erreur de chargement de Calendly', 'error');
        };

        script.onload = () => {
            const container = document.getElementById('calendly-container');
            if (!container) {
                console.error('Calendly container not found after script load');
                return;
            }

            Calendly.initInlineWidget({
                url: this.calendlyUrlValue,
                parentElement: container,
                prefill: {},
                utm: {}
            });

            window.addEventListener('message', async (e) => {
                if (e.data.event && e.data.event === 'calendly.event_scheduled') {
                    await this.handleCalendlyEventScheduled(e.data);
                }
            });
        };

        document.body.appendChild(script);
    }

    async handleCalendlyEventScheduled(data) {
        try {
            if (!this.serviceIdValue || !this.serviceSlugValue) {
                throw new Error('Données du service manquantes');
            }

            // Extraction correcte des IDs depuis la payload Calendly
            const eventId = data.payload.event.uri;
            const inviteeId = data.payload.invitee.uri;

            if (!eventId || !inviteeId) {
                throw new Error('Données de l\'événement Calendly manquantes');
            }

            const requestData = {
                serviceId: this.serviceIdValue,
                event: {
                    event_id: eventId,
                    invitee_id: inviteeId
                }
            };

            console.log('Sending reservation data:', requestData);

            const response = await fetch('/reservation/process-date', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData)
            });

            const responseData = await response.json();
            console.log('Server response:', responseData);

            if (!response.ok) {
                throw new Error(responseData.error || `Erreur serveur: ${response.status}`);
            }
            
            if (responseData.success) {
                window.location.href = `/reservation/paiement/${this.serviceSlugValue}`;
            } else {
                throw new Error(responseData.error || 'Une erreur est survenue');
            }
        } catch (error) {
            console.error('Erreur lors du traitement de la réservation:', error);
            this.showMessage(error.message || 'Une erreur est survenue lors du traitement de la réservation', 'error');
        }
    }

    async initializePayment() {
        if (!this.hasPaymentElementTarget) {
            console.error('Payment element target not found');
            return;
        }

        try {
            const stripe = Stripe(this.stripeKeyValue);
            const elements = stripe.elements({
                clientSecret: this.clientSecretValue,
                appearance: {
                    theme: 'stripe',
                    variables: {
                        colorPrimary: '#0a2540',
                        colorBackground: '#ffffff',
                        colorText: '#30313d',
                        colorDanger: '#df1b41',
                        fontFamily: 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
                        spacingUnit: '4px',
                        borderRadius: '4px'
                    }
                }
            });

            const paymentElement = elements.create('payment');
            await paymentElement.mount(this.paymentElementTarget);

            if (this.hasFormTarget) {
                this.formTarget.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    await this.handlePaymentSubmission(stripe, elements);
                });
            }
        } catch (error) {
            console.error('Erreur lors de l\'initialisation du paiement:', error);
            this.showMessage('Erreur lors de l\'initialisation du paiement', 'error');
        }
    }

    async handlePaymentSubmission(stripe, elements) {
        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.disabled = true;
        }
        
        if (this.hasLoadingElementTarget) {
            this.loadingElementTarget.classList.remove('hidden');
        }

        try {
            const { error } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: `${window.location.origin}/reservation/success`,
                }
            });

            if (error) {
                throw error;
            }
        } catch (error) {
            console.error('Erreur de paiement:', error);
            this.showMessage(error.message || 'Une erreur est survenue lors du paiement', 'error');
        } finally {
            if (this.hasSubmitButtonTarget) {
                this.submitButtonTarget.disabled = false;
            }
            if (this.hasLoadingElementTarget) {
                this.loadingElementTarget.classList.add('hidden');
            }
        }
    }

    showMessage(message, type = 'info') {
        if (!this.hasMessageElementTarget) {
            console.error('Message element target not found');
            return;
        }

        this.messageElementTarget.textContent = message;
        this.messageElementTarget.className = `message message-${type}`;
        this.messageElementTarget.classList.remove('hidden');
        
        setTimeout(() => {
            this.messageElementTarget.classList.add('hidden');
        }, 5000);
    }
}