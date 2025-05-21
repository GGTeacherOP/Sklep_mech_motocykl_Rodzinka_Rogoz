/**
 * Skrypt obsługujący funkcjonalność listy życzeń
 */

document.addEventListener('DOMContentLoaded', function() {
    // Obsługa przycisków dodawania do listy życzeń
    const wishlistButtons = document.querySelectorAll('.add-to-wishlist');
    
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            
            // Dodanie produktu do listy życzeń
            addToWishlist(productId, this);
        });
    });
    
    // Funkcja dodająca produkt do listy życzeń
    function addToWishlist(productId, button) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        
        fetch('wishlist-actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Wyświetlenie powiadomienia
                showNotification(data.message);
                
                // Aktualizacja ikony przycisku
                if (button) {
                    button.classList.add('in-wishlist');
                    
                    // Zmiana ikony przycisku
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.remove('ri-heart-line');
                        icon.classList.add('ri-heart-fill');
                    }
                }
                
                // Aktualizacja licznika w nagłówku
                updateWishlistCount(data.wishlist_count);
            } else if (data.redirect) {
                // Przekierowanie do logowania, jeśli użytkownik nie jest zalogowany
                window.location.href = data.redirect;
            } else {
                showNotification(data.message || 'Wystąpił błąd. Spróbuj ponownie.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Wystąpił błąd. Spróbuj ponownie.', 'error');
        });
    }
    
    // Funkcja aktualizująca licznik produktów w liście życzeń
    function updateWishlistCount(count) {
        const wishlistCountBadge = document.querySelector('a[href="wishlist.php"] span');
        
        if (count > 0) {
            if (wishlistCountBadge) {
                wishlistCountBadge.textContent = count;
            } else {
                // Jeśli badge nie istnieje, dodaj go
                const wishlistIcon = document.querySelector('a[href="wishlist.php"]');
                if (wishlistIcon) {
                    const badge = document.createElement('span');
                    badge.className = 'absolute -top-1 -right-1 bg-primary text-white text-xs rounded-full w-5 h-5 flex items-center justify-center';
                    badge.textContent = count;
                    wishlistIcon.appendChild(badge);
                }
            }
        } else if (wishlistCountBadge) {
            wishlistCountBadge.remove();
        }
    }
    
    // Funkcja wyświetlająca powiadomienia
    function showNotification(message, type = 'success') {
        // Jeśli istnieje już element powiadomienia, usuń go
        const existingNotification = document.getElementById('wishlist-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Tworzenie nowego elementu powiadomienia
        const notification = document.createElement('div');
        notification.id = 'wishlist-notification';
        notification.className = `fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg transition-opacity duration-300 ${type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="${type === 'success' ? 'ri-check-line' : 'ri-error-warning-line'} mr-2 text-lg"></i>
                <p>${message}</p>
            </div>
        `;
        
        // Dodanie powiadomienia do dokumentu
        document.body.appendChild(notification);
        
        // Usunięcie powiadomienia po 3 sekundach
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
