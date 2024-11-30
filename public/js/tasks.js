/****************************nouvelle tache *************************/

document.addEventListener('DOMContentLoaded', function () {
const urlParams = new URLSearchParams(window.location.search);
const newTaskId = urlParams.get('target_task');

if (newTaskId) {
    const newTaskElement = document.getElementById(`task-${newTaskId}`);
    if (newTaskElement) {
        newTaskElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        newTaskElement.classList.add('highlight');
        setTimeout(() => newTaskElement.classList.remove('highlight'), 1000);
    }
}
});

/********************  Menu de satut de taches  ******************* */

   function openPopup(taskId) {
    document.querySelectorAll('.popup').forEach(popup => popup.style.display = 'none');
    document.getElementById(`popup-${taskId}`).style.display = 'block';
}

// Fermer les popups en cliquant ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.task')) {
        document.querySelectorAll('.popup').forEach(popup => popup.style.display = 'none');
    }
});