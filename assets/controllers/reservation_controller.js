/**
 * Controller de réservation qui gère l'intégration avec Calendly et le traitement des rendez-vous
 * Utilise Stimulus.js pour la gestion des événements et la manipulation du DOM
 */
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Définition des cibles et valeurs utilisées dans le controller
    static targets = ['form', 'paymentElement', 'submitButton', 'loadingElement', 'messageElement', 'container']
    static values = {
        stripeKey: String,
        clientSecret: String,
        type: String,
        calendlyUrl: String,
        serviceSlug: String,
        serviceId: String
    }

    /**
     * Méthode appelée lors de l'initialisation du controller
     * Vérifie les paramètres nécessaires et initialise le bon type de réservation
     */
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

    /**
     * Initialise l'intégration avec Calendly
     * - Charge le script Calendly de manière asynchrone
     * - Configure le widget d'intégration
     * - Met en place les écouteurs d'événements
     */
    initializeCalendly() {
        // Vérification de la présence du conteneur Calendly
        const container = document.getElementById('calendly-container');
        if (!container) {
            console.error('Calendly container not found');
            this.showMessage('Erreur de chargement du calendrier', 'error');
            return;
        }

        // Vérifier si le script Calendly est déjà chargé
        if (window.Calendly) {
            this.initCalendlyWidget(container);
            return;
        }

        // Création et configuration du script Calendly
        const script = document.createElement('script');
        script.src = 'https://assets.calendly.com/assets/external/widget.js';
        script.async = true;
        script.defer = true;

        // Gestion des erreurs de chargement du script
        script.onerror = () => {
            console.error('Failed to load Calendly script');
            this.showMessage('Erreur de chargement de Calendly', 'error');
            container.classList.remove('loaded');
            this.hideLoadingSpinner(container);
        };

        // Callback exécuté une fois le script chargé
        script.onload = () => {
            if (!container) {
                console.error('Calendly container not found after script load');
                return;
            }

            this.initCalendlyWidget(container);
        };

        // Ajouter le script au document
        document.head.appendChild(script);
    }

    /**
     * Initialise le widget Calendly dans le conteneur spécifié
     * @param {HTMLElement} container - Le conteneur où initialiser le widget
     */
    initCalendlyWidget(container) {
        try {
            // Récupérer le nom du service depuis le titre
            const serviceTitle = document.querySelector('.service-title')?.textContent?.trim() || '';
            
            Calendly.initInlineWidget({
                url: this.calendlyUrlValue,
                parentElement: container,
                prefill: {
                    customAnswers: {
                        a1: serviceTitle // Pré-remplir avec le nom du service
                    }
                },
                utm: {},
                iframeAttributes: {
                    'allow-scripts': 'true',
                    'allow-same-origin': 'true',
                    'allow-popups': 'true',
                    'allow-forms': 'true',
                    'allow-top-navigation': 'true',
                    'sandbox': 'allow-scripts allow-same-origin allow-popups allow-forms allow-top-navigation'
                }
            });

            container.classList.add('loaded');
            this.hideLoadingSpinner(container);

            // Ajouter l'écouteur d'événements pour la communication avec l'iframe Calendly
            window.addEventListener('message', this.handleCalendlyMessage.bind(this));
        } catch (error) {
            console.error('Error initializing Calendly widget:', error);
            this.showMessage('Erreur d\'initialisation du calendrier', 'error');
            this.hideLoadingSpinner(container);
        }
    }

    /**
     * Gère les messages reçus de l'iframe Calendly
     * @param {MessageEvent} event - L'événement de message
     */
    handleCalendlyMessage(event) {
        if (event.data.event && event.data.event === 'calendly.event_scheduled') {
            this.handleCalendlyEventScheduled(event.data);
        }
    }

    /**
     * Cache le spinner de chargement
     * @param {HTMLElement} container - Le conteneur Calendly
     */
    hideLoadingSpinner(container) {
        const spinner = container.querySelector('.loading-spinner');
        if (spinner) {
            spinner.style.display = 'none';
        }
    }

    /**
     * Traite la réservation après qu'un rendez-vous ait été pris sur Calendly
     * @param {Object} data - Données de l'événement Calendly
     */
    async handleCalendlyEventScheduled(data) {
        try {
            // Vérification des données requises
            if (!this.serviceIdValue || !this.serviceSlugValue) {
                throw new Error('Données du service manquantes');
            }

            // Extraction des IDs depuis la payload Calendly
            const eventId = data.payload.event.uri;
            const inviteeId = data.payload.invitee.uri;

            if (!eventId || !inviteeId) {
                throw new Error('Données de l\'événement Calendly manquantes');
            }

            // Préparation des données pour l'API
            const requestData = {
                serviceId: this.serviceIdValue,
                event: {
                    event_id: eventId,
                    invitee_id: inviteeId
                }
            };

            console.log('Envoi des données de réservation:', requestData);

            /**
             * Appel API pour sauvegarder la réservation
             * Utilisation de fetch pour la communication avec le serveur
             */
            const response = await fetch('/reservation/process-date', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData)
            });

            // Traitement de la réponse
            const responseData = await response.json();
            console.log('Réponse du serveur:', responseData);

            if (!response.ok) {
                throw new Error(responseData.error || `Erreur serveur: ${response.status}`);
            }
            
            // Gestion de la réponse en fonction du succès
            if (responseData.success) {
                // Redirection vers la page de paiement en cas de succès
                window.location.href = `/reservation/paiement/${this.serviceSlugValue}`;
            } else {
                if (responseData.redirect) {
                    // Redirection personnalisée si spécifiée
                    window.location.href = responseData.redirect;
                } else {
                    throw new Error(responseData.error || 'Une erreur est survenue');
                }
            }
        } catch (error) {
            console.error('Erreur lors du traitement de la réservation:', error);
            this.showMessage(error.message || 'Une erreur est survenue lors du traitement de la réservation', 'error');
        }
    }

    /**
     * Initialise le système de paiement Stripe
     * - Configure les éléments de paiement Stripe
     * - Met en place les écouteurs d'événements pour le formulaire
     */
    async initializePayment() {
        // Vérification de la présence de l'élément de paiement
        if (!this.hasPaymentElementTarget) {
            console.error('Payment element target not found');
            return;
        }

        try {
            // Initialisation de Stripe avec la clé publique
            const stripe = Stripe(this.stripeKeyValue);
            
            // Configuration des éléments de paiement avec le client secret
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

            // Création et montage de l'élément de paiement
            const paymentElement = elements.create('payment');
            await paymentElement.mount(this.paymentElementTarget);

            // Configuration du formulaire de paiement
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

    /**
     * Gère la soumission du formulaire de paiement
     * @param {Object} stripe - Instance de Stripe
     * @param {Object} elements - Éléments de paiement Stripe
     */
    async handlePaymentSubmission(stripe, elements) {
        // Désactivation du bouton de soumission pendant le traitement
        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.disabled = true;
        }
        
        // Affichage de l'indicateur de chargement
        if (this.hasLoadingElementTarget) {
            this.loadingElementTarget.classList.remove('hidden');
        }

        try {
            // Confirmation du paiement avec Stripe
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
            // Réactivation du bouton et masquage de l'indicateur de chargement
            if (this.hasSubmitButtonTarget) {
                this.submitButtonTarget.disabled = false;
            }
            if (this.hasLoadingElementTarget) {
                this.loadingElementTarget.classList.add('hidden');
            }
        }
    }

    /**
     * Affiche un message à l'utilisateur avec gestion automatique de la disparition
     * @param {string} message - Le message à afficher
     * @param {string} type - Le type de message (success, error, info)
     */
    showMessage(message, type = 'info') {
        // Vérification de la présence de l'élément de message
        if (!this.hasMessageElementTarget) {
            console.error('Message element target not found');
            return;
        }

        // Configuration du message
        this.messageElementTarget.textContent = message;
        this.messageElementTarget.className = `message message-${type}`;
        this.messageElementTarget.classList.remove('hidden');
        
        // Disparition automatique du message après 5 secondes
        setTimeout(() => {
            this.messageElementTarget.classList.add('hidden');
        }, 5000);
    }
}