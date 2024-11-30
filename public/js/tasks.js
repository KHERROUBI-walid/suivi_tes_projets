/****************************nouvelle tache *************************/

document.addEventListener('DOMContentLoaded', function () {
const urlParams = new URLSearchParams(window.location.search);
const newTaskId = urlParams.get('new_task');

if (newTaskId) {
    const newTaskElement = document.getElementById(`task-${newTaskId}`);
    if (newTaskElement) {
        newTaskElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        newTaskElement.classList.add('highlight');
        setTimeout(() => newTaskElement.classList.remove('highlight'), 1000);
    }
}
});