if (document.getElementById('show-phone') !== null) {
    document.getElementById('show-phone').onclick = function() {
        this.style.display = 'none'; // Cache le texte "Afficher le numéro"
        document.getElementById('phone-number').style.display = 'block'; // Affiche le numéro
    };
}