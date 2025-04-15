// Configuration du bandeau de cookies pour Regards Singuliers
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si la bibliothèque cookieconsent est chargée
    if (typeof window.cookieconsent === 'undefined') {
        console.error('La bibliothèque cookieconsent n\'est pas chargée');
        return;
    }
    
    // Initialisation de Cookie Consent
    window.cookieconsent.initialise({
        palette: {
            // Utilisation des couleurs du site avec transparence
            "popup": { 
                "background": "rgba(5, 5, 5, 0.5)", 
                "text": "var(--color-white)" 
            },
            "button": { 
                "background": "var(--color-white)", 
                "text": "var(--color-black)" 
            }
        },
        theme: "classic",
        type: "opt-in",
        content: {
            "message": "Ce site utilise des cookies pour améliorer votre expérience. Souhaitez-vous les accepter ?",
            "allow": "J'accepte",
            "deny": "Je refuse",
            "link": "En savoir plus",
            "href": "/mentions-legales/gestion-cookies",
            "policy": 'Gestion des cookies',
            "target": '_self'
        },
        onStatusChange: function(status) {
            if (status === 'deny') {
                disableCookies();
            }
            if (status === 'allow') {
                enableCookies();
            }
        }
    });

    // Vérification du statut des cookies au chargement
    if (getCookie("cookieconsent_status") !== 'deny') {
        // Par défaut, les cookies sont activés
        enableCookies();
    }
});

/**
 * Récupère la valeur d'un cookie
 * @param {string} cname - Nom du cookie
 * @return {string} - Valeur du cookie ou chaîne vide
 */
function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

/**
 * Active les cookies sur le site
 */
function enableCookies() {
    // Activation des cookies analytiques, marketing, etc.
    // À personnaliser selon les besoins du site
    console.log("Cookies activés");
    
    // Exemple: activation de Google Analytics
    // if (typeof ga === 'function') {
    //     ga('set', 'anonymizeIp', false);
    // }
}

/**
 * Désactive les cookies sur le site
 */
function disableCookies() {
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
    
    console.log("Cookies désactivés");
    
    // Exemple: désactivation de Google Analytics
    // if (typeof ga === 'function') {
    //     ga('set', 'anonymizeIp', true);
    // }
}
