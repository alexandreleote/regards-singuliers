document.addEventListener('DOMContentLoaded', function() { 

    // Sélectionner tous les éléments de question 
    const faqQuestions = document.querySelectorAll('.faq-question'); 

    // Ouvrir le premier accordéon par défaut 
    const firstFaqItem = document.querySelector('.faq-item'); 

    if (firstFaqItem) { firstFaqItem.classList.add('active'); }
    
    // Ajouter un écouteur d'événement à chaque question 
    faqQuestions.forEach(question => { 
        question.addEventListener('click', function() { 
        
            // Récupérer l'élément parent (faq-item) 
            const faqItem = this.parentElement; 

            // Vérifier si l'élément est déjà actif 
            const isActive = faqItem.classList.contains('active'); 

            // Fermer tous les autres éléments 
            document.querySelectorAll('.faq-item').forEach(item => { 
                item.classList.remove('active'); 
            }); 

            // Si l'élément n'était pas actif, l'ouvrir 
            if (!isActive) {
                faqItem.classList.add('active'); 
            } 
        }); 
    }); 

    // Animation du header lors du défilement 
    window.addEventListener('scroll', function() { 
        const header = document.querySelector('.site-header'); 
        if (window.scrollY > 50) { 
            header.classList.add('scrolled'); 
        } else { 
            header.classList.remove('scrolled'); 
        } 
    }); 
});