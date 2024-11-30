document.addEventListener('DOMContentLoaded', function () {
    // Sélection de tous les liens qui ouvrent une modale
    const modaleTriggers = document.querySelectorAll('[data-open-modale]');
    const fermerBoutons = document.querySelectorAll('.bouton-fermer');

    modaleTriggers.forEach(trigger => {
        const modaleId = trigger.getAttribute('data-open-modale');
        const modale = document.getElementById(modaleId);

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            if (modale) {
                modale.classList.add('afficher');
            }
        });
    });

    fermerBoutons.forEach(bouton => {
        bouton.addEventListener('click', function () {
            const modale = bouton.closest('.fond-modale');
            if (modale) {
                modale.classList.remove('afficher');
            }
        });
    });

    // Masquer la modale en cliquant à l'extérieur du contenu
    document.querySelectorAll('.fond-modale').forEach(modale => {
        modale.addEventListener('click', function (e) {
            if (e.target === modale) {
                modale.classList.remove('afficher');
            }
        });
    });
});
