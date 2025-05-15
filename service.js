function showBookingModal(mechanicName) {
    document.getElementById('mechanicName').value = mechanicName;
    document.getElementById('bookingModal').classList.remove('hidden');
    document.getElementById('bookingModal').classList.add('flex');
}

function hideBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
    document.getElementById('bookingModal').classList.remove('flex');
}

function showConfirmationModal() {
    document.getElementById('confirmationModal').classList.remove('hidden');
    document.getElementById('confirmationModal').classList.add('flex');
}

function hideConfirmationModal() {
    document.getElementById('confirmationModal').classList.add('hidden');
    document.getElementById('confirmationModal').classList.remove('flex');
}

function showMoreReviews() {
    document.getElementById('reviewsModal').classList.remove('hidden');
    document.getElementById('reviewsModal').classList.add('flex');
    document.body.style.overflow = 'hidden'; // Blokuje przewijanie strony
}

function hideMoreReviews() {
    document.getElementById('reviewsModal').classList.add('hidden');
    document.getElementById('reviewsModal').classList.remove('flex');
    document.body.style.overflow = ''; // Przywraca przewijanie strony
}

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    hideBookingModal();
    showConfirmationModal();
});