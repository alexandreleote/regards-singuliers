// Les styles sont maintenant importés via app.css

// Global variables from Twig template
const stripePublicKey = document.querySelector('meta[name="stripe-public-key"]')?.getAttribute('content');
const userEmail = document.querySelector('meta[name="user-email"]')?.getAttribute('content');

// Throw error if global variables are missing
if (!stripePublicKey || !userEmail) {
    console.error('Missing required global variables for reservation');
}

// Service Selection Module
const ServiceSelector = {
    init() {
        this.bindEvents();
    },
    
    bindEvents() {
        const serviceButtons = document.querySelectorAll('.select-service');
        serviceButtons.forEach(button => {
            button.addEventListener('click', this.handleServiceSelection.bind(this));
        });
    },
    
    handleServiceSelection(event) {
        const serviceCard = event.target.closest('.service-card');
        const selectedServiceId = serviceCard.dataset.serviceId;
        
        // Remove selection from all cards
        document.querySelectorAll('.service-card').forEach(card => {
            card.classList.remove('selected-service');
        });
        
        // Add selection to current card
        serviceCard.classList.add('selected-service');
        
        // Show calendar section
        document.getElementById('calendar-section').style.display = 'block';
        
        // Initialize Calendly
        CalendlyManager.init(selectedServiceId);
    }
};

// Calendly Management Module
const CalendlyManager = {
    selectedServiceId: null,
    
    init(serviceId) {
        this.selectedServiceId = serviceId;
        this.loadCalendlyWidget();
        this.bindCalendlyEvents();
    },
    
    loadCalendlyWidget() {
        Calendly.initInlineWidget({
            url: 'https://calendly.com/votre-compte/consultation',
            parentElement: document.getElementById('calendar-container'),
            prefill: {
                email: userEmail
            },
            minBookingNotice: 1440 // 24 heures minimum
        });
    },
    
    bindCalendlyEvents() {
        window.addEventListener('message', this.handleCalendlyEvent.bind(this));
    },
    
    handleCalendlyEvent(e) {
        if (e.data.event === 'calendly.event_scheduled') {
            const selectedDate = e.data.payload.event.start_time;
            
            // Show payment section
            document.getElementById('payment-section').style.display = 'block';
            
            // Initialize Stripe with selected date and service
            StripePayment.init(this.selectedServiceId, selectedDate);
        }
    }
};

// Stripe Payment Module
const StripePayment = {
    stripe: null,
    selectedServiceId: null,
    selectedDate: null,
    
    init(serviceId, selectedDate) {
        this.selectedServiceId = serviceId;
        this.selectedDate = selectedDate;
        
        // Initialize Stripe
        this.stripe = Stripe(stripePublicKey);
        this.setupStripeElements();
        this.bindSubmitEvent();
    },
    
    setupStripeElements() {
        const elements = this.stripe.elements();
        const card = elements.create('card');
        card.mount('#card-element');
        this.card = card;
    },
    
    bindSubmitEvent() {
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', this.handlePaymentSubmit.bind(this));
    },
    
    async handlePaymentSubmit(event) {
        event.preventDefault();
        
        try {
            // Create reservation
            const reservationResponse = await this.createReservation();
            
            // Confirm card payment
            const paymentResult = await this.confirmCardPayment(reservationResponse);
            
            // Handle payment result
            if (paymentResult.error) {
                this.handlePaymentError(paymentResult.error);
            } else {
                await this.confirmReservation(reservationResponse.reservation_id);
                this.redirectToProfile();
            }
        } catch (error) {
            console.error('Payment process error:', error);
            document.getElementById('payment-message').textContent = 'Une erreur est survenue lors du paiement.';
        }
    },
    
    async createReservation() {
        const response = await fetch('/reservation/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                service_id: this.selectedServiceId,
                booking_date: this.selectedDate
            })
        });
        return response.json();
    },
    
    async confirmCardPayment(reservationData) {
        return this.stripe.confirmCardPayment(reservationData.client_secret, {
            payment_method: {
                card: this.card,
                billing_details: {
                    email: userEmail
                }
            }
        });
    },
    
    handlePaymentError(error) {
        document.getElementById('payment-message').textContent = error.message;
    },
    
    async confirmReservation(reservationId) {
        await fetch(`/reservation/confirm/${reservationId}`, {
            method: 'POST'
        });
    },
    
    redirectToProfile() {
        window.location.href = '/profile';
    }
};

// External Script Loader
const ExternalScriptLoader = {
    loadScripts() {
        const scripts = [
            { id: 'stripe-script', src: 'https://js.stripe.com/v3/' },
            { id: 'calendly-script', src: 'https://assets.calendly.com/assets/external/widget.js' }
        ];
        
        scripts.forEach(script => {
            if (!document.getElementById(script.id)) {
                const scriptElement = document.createElement('script');
                scriptElement.id = script.id;
                scriptElement.src = script.src;
                document.head.appendChild(scriptElement);
            }
        });
    }
};

// Initialization
document.addEventListener('DOMContentLoaded', () => {
    ExternalScriptLoader.loadScripts();
    ServiceSelector.init();
});
