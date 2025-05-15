function showConfirmationModal() {
    document.getElementById('confirmationModal').classList.remove('hidden');
    document.getElementById('confirmationModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function hideConfirmationModal() {
    document.getElementById('confirmationModal').classList.add('hidden');
    document.getElementById('confirmationModal').classList.remove('flex');
    document.body.style.overflow = '';
}

document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Tutaj można dodać kod do wysyłania formularza na serwer
    
    // Wyświetl modal potwierdzenia
    showConfirmationModal();
    
    // Wyczyść formularz
    this.reset();
}); 