document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.querySelector('.ri-menu-line');
            if (menuToggle) {
                menuToggle.addEventListener('click', function () {
                    alert('Menu mobilne zostanie zaimplementowane');
                });
            }

            const cartIcon = document.getElementById('cartIcon');
            const cartDropdown = document.getElementById('cartDropdown');
            let isCartOpen = false;

            document.addEventListener('click', function (event) {
                if (cartIcon.contains(event.target)) {
                    isCartOpen = !isCartOpen;
                    cartDropdown.classList.toggle('hidden');
                } else if (!cartDropdown.contains(event.target)) {
                    isCartOpen = false;
                    cartDropdown.classList.add('hidden');
                }
            });

            window.updateQuantity = function (itemId, action) {
                const item = document.querySelector([onclick *= "updateQuantity(${itemId}"]).closest('.cart-item');
                const input = item.querySelector('input');
                let value = parseInt(input.value);

                if (action === 'increase') {
                    value++;
                } else if (action === 'decrease') {
                    value = Math.max(1, value - 1);
                } else if (action === 'input') {
                    value = Math.max(1, value);
                }

                input.value = value;
                updateCartTotal();
            };

            window.removeItem = function (itemId) {
                const item = document.querySelector([onclick *= "removeItem(${itemId}"]).closest('.cart-item');
                item.remove();
                updateCartCount();
                updateCartTotal();
            };

            function updateCartCount() {
                const cartCount = document.getElementById('cartCount');
                const itemCount = document.querySelectorAll('.cart-item').length;
                cartCount.textContent = itemCount;
                if (itemCount === 0) {
                    cartDropdown.classList.add('hidden');
                    isCartOpen = false;
                }
            }

            function updateCartTotal() {
                const items = document.querySelectorAll('.cart-item');
                let total = 0;

                items.forEach(item => {
                    const price = parseInt(item.querySelector('.font-medium').textContent.replace(/[^\d]/g, ''));
                    const quantity = parseInt(item.querySelector('input').value);
                    total += price * quantity;
                });

                const totalElement = document.querySelector('.font-bold span:last-child');
                totalElement.textContent = total.toLocaleString() + ' zł';

                const subtotalElement = document.querySelector('.text-sm .font-medium:last-child');
                subtotalElement.textContent = total.toLocaleString() + ' zł';
            }
        });