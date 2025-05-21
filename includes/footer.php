<footer class="bg-gray-800 text-white pt-16 pb-8 mt-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12">
                <div>
                    <h3 class="text-xl font-bold mb-6">O nas</h3>
                    <p class="text-gray-400 mb-6">MotoShop to kompleksowy sklep motocyklowy oferujący części, akcesoria,
                        serwis oraz motocykle używane. Jesteśmy na rynku od 2005 roku.</p>
                    <div class="flex space-x-4">
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary transition">
                            <i class="ri-facebook-fill"></i>
                        </a>
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary transition">
                            <i class="ri-instagram-line"></i>
                        </a>
                        <a href="#"
                            class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary transition">
                            <i class="ri-youtube-line"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-6">Sklep</h3>
                    <ul class="space-y-3">
                        <li><a href="ProductCatalog.php?category=helmets" class="text-gray-400 hover:text-primary transition">Kaski</a></li>
                        <li><a href="ProductCatalog.php?category=clothing" class="text-gray-400 hover:text-primary transition">Odzież</a></li>
                        <li><a href="ProductCatalog.php?category=parts" class="text-gray-400 hover:text-primary transition">Części</a></li>
                        <li><a href="ProductCatalog.php?category=oils" class="text-gray-400 hover:text-primary transition">Oleje i chemia</a></li>
                        <li><a href="ProductCatalog.php?category=accessories" class="text-gray-400 hover:text-primary transition">Akcesoria</a></li>
                        <li><a href="ProductCatalog.php?category=promotions" class="text-gray-400 hover:text-primary transition">Promocje</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-6">Usługi</h3>
                    <ul class="space-y-3">
                        <li><a href="service.php" class="text-gray-400 hover:text-white transition">Serwis motocyklowy</a></li>
                        <li><a href="service.php?service=przeglad" class="text-gray-400 hover:text-white transition">Przeglądy okresowe</a></li>
                        <li><a href="used-motorcycles.php" class="text-gray-400 hover:text-white transition">Motocykle używane</a></li>
                        <li><a href="service.php?service=doradztwo" class="text-gray-400 hover:text-white transition">Doradztwo techniczne</a></li>
                        <li><a href="service.php?service=transport" class="text-gray-400 hover:text-white transition">Transport motocykli</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-6">Kontakt</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start">
                            <div class="w-5 h-5 flex items-center justify-center mt-1 mr-3">
                                <i class="ri-map-pin-line text-primary"></i>
                            </div>
                            <span class="text-gray-400">ul. Motocyklowa 123<br>00-001 Warszawa</span>
                        </li>
                        <li class="flex items-center">
                            <div class="w-5 h-5 flex items-center justify-center mr-3">
                                <i class="ri-phone-line text-primary"></i>
                            </div>
                            <span class="text-gray-400">+48 123 456 789</span>
                        </li>
                        <li class="flex items-center">
                            <div class="w-5 h-5 flex items-center justify-center mr-3">
                                <i class="ri-mail-line text-primary"></i>
                            </div>
                            <span class="text-gray-400">kontakt@motoshop.pl</span>
                        </li>
                        <li class="flex items-center">
                            <div class="w-5 h-5 flex items-center justify-center mr-3">
                                <i class="ri-time-line text-primary"></i>
                            </div>
                            <span class="text-gray-400">Pon-Pt: 9:00 - 18:00<br>Sob: 9:00 - 14:00</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 border-t border-gray-700 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">© 2025 MotoShop. Wszelkie prawa zastrzeżone.</p>
                <div class="flex items-center space-x-4">
                    <a href="privacy-policy.php" class="text-gray-400 hover:text-white text-sm transition">Polityka prywatności</a>
                    <a href="terms.php" class="text-gray-400 hover:text-white text-sm transition">Regulamin</a>
                    <a href="sitemap.php" class="text-gray-400 hover:text-white text-sm transition">Mapa strony</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuButton = document.getElementById('mobileMenuButton');
        const mobileMenu = document.getElementById('mobileMenu');
        
        if (menuButton && mobileMenu) {
            menuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });
        }
    });
    </script>
    
    <?php if (isset($extra_js)): echo $extra_js; endif; ?>
</body>
</html>
