// Configuration du bandeau de cookies pour Regards Singuliers
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si la bibliothèque cookieconsent est chargée
    if (typeof window.cookieconsent === 'undefined') {
        console.error('La bibliothèque cookieconsent n\'est pas chargée');
        return;
    }
    
    
    // Fonction pour récupérer la valeur d'un cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    // Initialisation du bandeau de cookies uniquement s'il n'est pas déjà présent
    if (!document.querySelector('.cc-window')) {
        // Options de configuration pour Cookie Consent
        const cookieOptions = {
            palette: {
                // Utilisation des couleurs du site avec transparence
                "popup": { 
                    "background": "#000000", 
                    "text": "#FFFFFF" 
                },
                "button": { 
                    "background": "#FFFFFF", 
                    "text": "#000000" 
                }
            },
            theme: "edgeless",
            type: "opt-in",
            classContent: '',
            layout: 'categories',
            layouts: {
                'categories': '{{messagelink}}{{categories}}{{compliance}}'
            },
            elements: {
                messagelink: '<span id="cookieconsent:desc" class="cc-message">{{message}} <a aria-label="En savoir plus sur les cookies" tabindex="0" class="cc-link" href="{{href}}" target="{{target}}">{{link}}</a></span>',
                categories: '<ul class="cc-categories">{{categories}}</ul>',
                category: '<li class="cc-category">{{category}}</li>',
                compliance: '<div class="cc-compliance cc-custom-buttons">{{customize}}{{accept}}{{reject}}</div>',
                customize: '<a aria-label="{{customize}}" tabindex="0" class="cc-btn cc-customize">{{customize}}</a>',
                accept: '<a aria-label="{{allow}}" tabindex="0" class="cc-btn cc-allow">{{allow}}</a>',
                reject: '<a aria-label="{{deny}}" tabindex="0" class="cc-btn cc-deny">{{deny}}</a>',
            },
            position: 'bottom',
            dismissOnScroll: false,
            dismissOnTimeout: false,
            content: {
                "message": "Pour améliorer votre expérience, nous (et nos partenaires) stockons et/ou accédons à des informations sur votre terminal (cookie ou équivalent) avec votre accord pour tous nos sites et applications, sur vos terminaux mobiles.",
                "allow": "Accepter tout",
                "deny": "Continuer sans accepter",
                "link": "En savoir plus",
                "href": "/mentions-legales/gestion-cookies",
                "policy": 'Gestion des cookies',
                "target": '_self',
                "customize": "Personnaliser vos choix",
                "categories": {
                    "necessary": {
                        "title": "Cookies nécessaires",
                        "description": "Notre site Web peut utiliser ces cookies pour :",
                        "toggle": {
                            "value": "necessary",
                            "enabled": true,
                            "readonly": true
                        }
                    },
                    "analytics": {
                        "title": "Mesurer l'audience",
                        "description": "Mesurer l'audience de la publicité sur notre site, sans profilage",
                        "toggle": {
                            "value": "analytics",
                            "enabled": false,
                            "readonly": false
                        }
                    },
                    "targeting": {
                        "title": "Afficher des publicités",
                        "description": "Afficher des publicités personnalisées basées sur votre navigation et votre profil",
                        "toggle": {
                            "value": "targeting",
                            "enabled": false,
                            "readonly": false
                        }
                    },
                    "personalization": {
                        "title": "Personnalisation",
                        "description": "Personnaliser notre contenu éditorial en fonction de votre navigation",
                        "toggle": {
                            "value": "personalization",
                            "enabled": false,
                            "readonly": false
                        }
                    },
                    "social": {
                        "title": "Réseaux sociaux",
                        "description": "Vous permettre de partager du contenu sur les réseaux sociaux ou des plateformes présentes sur notre site internet",
                        "toggle": {
                            "value": "social",
                            "enabled": false,
                            "readonly": false
                        }
                    }
                }
            },
            onStatusChange: function(status, chosenCategories) {
                if (status === 'deny') {
                    disableCookies();
                }
                else if (status === 'allow') {
                    // Enregistrer toutes les catégories dans un cookie
                    const allCategories = ['necessary', 'analytics', 'targeting', 'personalization', 'social'];
                    const categoriesStr = encodeURIComponent(JSON.stringify(allCategories));
                    
                    // Définir le domaine pour s'assurer que les cookies sont disponibles sur tout le site
                    const domain = window.location.hostname;
                    
                    // Enregistrer les cookies avec des options complètes
                    document.cookie = `cookieconsent_categories=${categoriesStr}; path=/; domain=${domain}; max-age=31536000; SameSite=Lax`; // 1 an
                    
                    console.log('Cookies acceptés (tous):', {
                        categories: allCategories,
                        status: 'allow'
                    });
                    
                    enableCookies(allCategories);
                }
                else if (status === 'customize') {
                    // Gérer les catégories personnalisées
                    const enabledCategories = Object.keys(chosenCategories).filter(cat => chosenCategories[cat]);
                    
                    // Toujours inclure la catégorie 'necessary'
                    if (!enabledCategories.includes('necessary')) {
                        enabledCategories.push('necessary');
                    }
                    
                    // Définir le domaine pour s'assurer que les cookies sont disponibles sur tout le site
                    const domain = window.location.hostname;
                    
                    // Enregistrer les catégories dans un cookie
                    const categoriesStr = encodeURIComponent(JSON.stringify(enabledCategories));
                    document.cookie = `cookieconsent_categories=${categoriesStr}; path=/; domain=${domain}; max-age=31536000; SameSite=Lax`; // 1 an
                    
                    console.log('Cookies personnalisés enregistrés:', {
                        categories: enabledCategories,
                        status: 'customize'
                    });
                    
                    enableCookies(enabledCategories);
                }
            }
        };
        
        // Initialisation de Cookie Consent
        const cookiePopup = window.cookieconsent.initialise(cookieOptions);
        

    }
    
    // Vérification du statut des cookies au chargement
    try {
        const cookieStatus = getCookie("cookieconsent_status");
        if (cookieStatus !== 'deny') {
            // Par défaut, les cookies sont activés
            enableCookies();
        }
    } catch (error) {
        console.error('Erreur lors de la vérification du statut des cookies:', error);
    }
});

/**
 * Active les cookies sur le site
 * @param {Array|undefined} categories - Catégories de cookies à activer
 */
function enableCookies(categories) {
    // Activation des cookies analytiques, marketing, etc.
    // À personnaliser selon les besoins du site
    
    try {
        // Si aucune catégorie n'est spécifiée, activer tous les cookies
        if (!categories) {
            // Activer tous les cookies
            console.debug('Activation de tous les cookies');
            // Exemple: activation de Google Analytics
            // if (typeof ga === 'function') {
            //     ga('set', 'anonymizeIp', false);
            // }
        } else {
            // Activer uniquement les catégories spécifiées
            console.debug('Activation des cookies pour les catégories:', categories);
            
            // Exemple d'activation conditionnelle
            if (categories.includes('analytics')) {
                // Activer les cookies d'analyse
                // if (typeof ga === 'function') {
                //     ga('set', 'anonymizeIp', false);
                // }
            }
            
            if (categories.includes('targeting')) {
                // Activer les cookies de ciblage publicitaire
            }
            
            if (categories.includes('personalization')) {
                // Activer les cookies de personnalisation
            }
            
            if (categories.includes('social')) {
                // Activer les cookies des réseaux sociaux
            }
        }
        
        return true; // Indique que la fonction s'est exécutée avec succès
    } catch (error) {
        console.error('Erreur lors de l\'activation des cookies:', error);
        return false;
    }
}

/**
 * Désactive les cookies sur le site
 */
function disableCookies() {
    try {
        // Désactivation des cookies analytiques, marketing, etc.
        // À personnaliser selon les besoins du site
        
        // Exemple: désactivation de Google Analytics
        // if (typeof ga === 'function') {
        //     ga('set', 'anonymizeIp', true);
        // }
        
        // Définir le domaine pour s'assurer que les cookies sont supprimés correctement
        const domain = window.location.hostname;
        
        // Supprimer les cookies avec le domaine spécifié
        document.cookie = `cookieconsent_categories=; path=/; domain=${domain}; expires=Thu, 01 Jan 1970 00:00:00 GMT`;
        
        // Supprimer également sans le domaine spécifié (pour compatibilité)
        document.cookie = "cookieconsent_categories=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT";
        
        console.log('Cookies désactivés');
        
        // Suppression de tous les cookies sauf cookieconsent_status
        var cookies = document.cookie.split(";");
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i];
            var eqPos = cookie.indexOf("=");
            var name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
            if (!name.includes("cookieconsent_status")) {
                // Supprime le cookie du domaine
                document.cookie = name + "=;domain=" + window.location.hostname + ";path=/;max-age=0";
                document.cookie = name + "=;domain=." + window.location.hostname + ";path=/;max-age=0";
                document.cookie = name + "=;path=/;max-age=0";
            }
        }
        
        return true; // Indique que la fonction s'est exécutée avec succès
    } catch (error) {
        console.error('Erreur lors de la désactivation des cookies:', error);
        return false;
    }
}
