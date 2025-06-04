<?php
// Zabezpieczenie przed bezpośrednim dostępem do pliku
if (!defined('ADMIN_PANEL')) {
    header("Location: ../login.php");
    exit;
}

// Określenie aktywnej strony
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Pobranie roli administratora, jeśli nie jest ustawiona, domyślnie 'admin'
$admin_role = $_SESSION['admin_role'] ?? 'admin';
?>

<!-- Sidebar -->
<aside class="admin-sidebar h-screen bg-white shadow-md fixed top-0 left-0 z-10 overflow-y-auto">
    <div class="p-4 border-b">
        <div class="flex items-center justify-center">
            <h1 class="text-xl font-bold text-blue-600">MotoShop Admin</h1>
        </div>
    </div>
    
    <div class="p-4 border-b">
        <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                <i class="ri-user-line text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="font-medium"><?php echo $_SESSION['admin_name'] ?? 'Administrator'; ?></p>
                <p class="text-xs text-gray-500">
                    <?php 
                    // Wyświetlanie roli administratora
                    $role_display = '';
                    switch($admin_role) {
                        case 'admin':
                            $role_display = 'Administrator';
                            break;
                        case 'mechanic':
                            $role_display = 'Mechanik';
                            break;
                        case 'owner':
                            $role_display = 'Właściciel';
                            break;
                        default:
                            $role_display = ucfirst($admin_role);
                    }
                    echo $role_display;
                    ?>
                </p>
            </div>
        </div>
    </div>
    
    <nav class="p-4">
        <ul class="space-y-1">
            <!-- Dashboard widoczny dla wszystkich -->    
            <li>
                <a href="index.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'index' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-dashboard-line mr-3 text-lg"></i> 
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Analityka widoczna tylko dla administratora i właściciela -->
            <?php if ($admin_role === 'admin' || $admin_role === 'owner'): ?>
            <li>
                <a href="analytics.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'analytics' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-bar-chart-box-line mr-3 text-lg"></i> 
                    <span>Analityka</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Zarządzanie sklepem widoczne tylko dla administratora i właściciela -->
            <?php if ($admin_role === 'admin' || $admin_role === 'owner'): ?>
            <li class="mt-2 pt-2 border-t">
                <p class="text-xs font-semibold text-gray-500 px-4 py-1 uppercase">Sklep</p>
            </li>
            <li>
                <a href="manage_products.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'manage_products' || $current_page === 'products' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-shopping-bag-line mr-3 text-lg"></i> 
                    <span>Produkty</span>
                </a>
            </li>
            <li>
                <a href="categories.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'categories' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-price-tag-3-line mr-3 text-lg"></i> 
                    <span>Kategorie</span>
                </a>
            </li>
            <li>
                <a href="orders.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'orders' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-file-list-3-line mr-3 text-lg"></i> 
                    <span>Zamówienia</span>
                </a>
            </li>
            <li>
                <a href="settings.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'settings' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-settings-3-line mr-3 text-lg"></i> 
                    <span>Ustawienia</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Zarządzanie użytkownikami widoczne tylko dla administratora -->
            <?php if ($admin_role === 'admin'): ?>
            <li class="mt-2 pt-2 border-t">
                <p class="text-xs font-semibold text-gray-500 px-4 py-1 uppercase">Użytkownicy</p>
            </li>
            <li>
                <a href="users.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'users' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-user-settings-line mr-3 text-lg"></i> 
                    <span>Użytkownicy</span>
                </a>
            </li>
            <li>
                <a href="administrators.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'administrators' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-shield-user-line mr-3 text-lg"></i> 
                    <span>Administratorzy</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Wiadomości i recenzje widoczne dla wszystkich -->    
            <li class="mt-2 pt-2 border-t">
                <p class="text-xs font-semibold text-gray-500 px-4 py-1 uppercase">Komunikacja</p>
            </li>
            <li>
                <a href="messages.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'messages' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-message-2-line mr-3 text-lg"></i> 
                    <span>Wiadomości</span>
                    <?php
                    // Pobieranie liczby nieprzeczytanych wiadomości
                    $unread_query = "SELECT COUNT(*) as unread_count FROM contact_messages WHERE status = 'new'";
                    $unread_result = $conn->query($unread_query);
                    $unread_count = 0;
                    
                    if ($unread_result && $unread_result->num_rows > 0) {
                        $unread_data = $unread_result->fetch_assoc();
                        $unread_count = $unread_data['unread_count'];
                    }
                    
                    if ($unread_count > 0):
                    ?>
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="reviews.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'reviews' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-star-line mr-3 text-lg"></i> 
                    <span>Recenzje</span>
                    <?php
                    // Pobieranie liczby oczekujących recenzji
                    $pending_reviews_query = "SELECT COUNT(*) as pending_count FROM product_reviews WHERE status = 'pending'";
                    $pending_reviews_result = $conn->query($pending_reviews_query);
                    $pending_reviews_count = 0;
                    
                    if ($pending_reviews_result && $pending_reviews_result->num_rows > 0) {
                        $pending_reviews_data = $pending_reviews_result->fetch_assoc();
                        $pending_reviews_count = $pending_reviews_data['pending_count'];
                    }
                    
                    if ($pending_reviews_count > 0):
                    ?>
                    <span class="ml-auto bg-yellow-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $pending_reviews_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <!-- Sekcja serwisu widoczna dla wszystkich, ale głównie dla mechanika -->
            <li class="mt-2 pt-2 border-t">
                <p class="text-xs font-semibold text-gray-500 px-4 py-1 uppercase">Serwis</p>
            </li>
            <li>
                <a href="service_bookings.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'service_bookings' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-calendar-line mr-3 text-lg"></i> 
                    <span>Rezerwacje</span>
                </a>
            </li>
            <li>
                <a href="mechanics.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'mechanics' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-user-settings-line mr-3 text-lg"></i> 
                    <span>Mechanicy</span>
                </a>
            </li>
            <li>
                <a href="services.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'services' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-tools-line mr-3 text-lg"></i> 
                    <span>Usługi</span>
                </a>
            </li>
            
            <!-- Sekcja motocykli używanych -->
            <li class="mt-2 pt-2 border-t">
                <p class="text-xs font-semibold text-gray-500 px-4 py-1 uppercase">Motocykle Używane</p>
            </li>
            <li>
                <a href="used_motorcycles.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'used_motorcycles' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-motorcycle-line mr-3 text-lg"></i> 
                    <span>Lista motocykli</span>
                </a>
            </li>
            <li>
                <a href="motorcycle_form.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'motorcycle_form' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-add-circle-line mr-3 text-lg"></i> 
                    <span>Dodaj motocykl</span>
                </a>
            </li>
            <li>
                <a href="motorcycle_viewings.php" class="flex items-center py-2 px-4 rounded-lg <?php echo $current_page === 'motorcycle_viewings' ? 'bg-blue-100 text-blue-700' : 'text-gray-700 hover:bg-gray-100'; ?>">
                    <i class="ri-eye-line mr-3 text-lg"></i> 
                    <span>Rezerwacje oględzin</span>
                </a>
            </li>
			
			<!-- Link do strony głównej -->
            <li class="pt-4 mt-4 border-t">
                <a href="../index.php" class="flex items-center py-2 px-4 rounded-lg text-gray-700 hover:bg-gray-100" target="_blank">
                    <i class="ri-external-link-line mr-3 text-lg"></i> 
                    <span>Przejdź do strony głównej</span>
                </a>
            </li>
            
            <li class="pt-2 mt-2 border-t">
                <a href="logout.php" class="flex items-center py-2 px-4 rounded-lg text-red-600 hover:bg-red-50">
                    <i class="ri-logout-box-r-line mr-3 text-lg"></i> 
                    <span>Wyloguj się</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
