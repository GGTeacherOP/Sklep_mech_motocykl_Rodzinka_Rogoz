<?php
require_once 'config.php';

// Inicjalizacja zmiennych koszyka i listy życzeń
$cart_count = 0;
$wishlist_count = 0;

// Obsługa koszyka na podstawie sesji lub zalogowanego użytkownika
if (isLoggedIn()) {
    // Pobranie liczby produktów w koszyku dla zalogowanego użytkownika
    $user_id = $_SESSION['user_id'];
    
    $cart_query = "SELECT COUNT(ci.id) as count FROM cart_items ci 
                  JOIN carts c ON ci.cart_id = c.id 
                  WHERE c.user_id = $user_id";
    
    $cart_result = $conn->query($cart_query);
    if ($cart_result && $cart_result->num_rows > 0) {
        $row = $cart_result->fetch_assoc();
        $cart_count = $row['count'];
    }
    
    // Pobranie liczby produktów w liście życzeń
    $wishlist_query = "SELECT COUNT(wi.id) as count FROM wishlist_items wi 
                      JOIN wishlists w ON wi.wishlist_id = w.id 
                      WHERE w.user_id = $user_id";
    
    $wishlist_result = $conn->query($wishlist_query);
    if ($wishlist_result && $wishlist_result->num_rows > 0) {
        $row = $wishlist_result->fetch_assoc();
        $wishlist_count = $row['count'];
    }
} else if (isset($_SESSION['cart_items'])) {
    // Dla niezalogowanych użytkowników używamy sesji
    $cart_count = count($_SESSION['cart_items']);
    
    if (isset($_SESSION['wishlist_items'])) {
        $wishlist_count = count($_SESSION['wishlist_items']);
    }
}

// Określamy aktywną stronę na podstawie nazwy pliku
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - MotoShop' : 'MotoShop - Twój kompleksowy sklep motocyklowy'; ?></title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#e63946', secondary: '#457b9d' }, borderRadius: { 'none': '0px', 'sm': '4px', DEFAULT: '8px', 'md': '12px', 'lg': '16px', 'xl': '20px', '2xl': '24px', '3xl': '32px', 'full': '9999px', 'button': '8px' } } } }</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <?php if ($current_page == 'ProductCatalog.php'): ?>
    <link rel="stylesheet" href="assets/css/ProductCatalogStyles.css">
    <?php elseif ($current_page == 'used-motorcycles.php'): ?>
    <link rel="stylesheet" href="assets/css/used-motorcycles.css">
    <?php else: ?>
    <link rel="stylesheet" href="assets/css/mainStyles.css">
    <?php endif; ?>
    <?php if (isset($extra_css)): echo $extra_css; endif; ?>
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <a href="index.php" class="text-3xl font-['Pacifico'] text-primary">MotoShop</a>
                <div class="hidden md:flex items-center w-1/3 relative">
                    <form action="search.php" method="GET">
                        <input type="text" name="query" placeholder="Szukaj produktów..." 
                            class="w-full py-2 px-4 rounded border border-gray-300 focus:outline-none focus:border-primary text-sm">
                        <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 flex items-center justify-center">
                            <i class="ri-search-line text-gray-500"></i>
                        </button>
                    </form>
                </div>
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="ProductCatalog.php" class="text-gray-700 hover:text-primary font-medium whitespace-nowrap <?php echo $current_page == 'ProductCatalog.php' ? 'text-primary' : ''; ?>">Sklep</a>
                    <a href="service.php" class="text-gray-700 hover:text-primary font-medium whitespace-nowrap <?php echo $current_page == 'service.php' ? 'text-primary' : ''; ?>">Serwis</a>
                    <a href="used-motorcycles.php" class="text-gray-700 hover:text-primary font-medium whitespace-nowrap <?php echo $current_page == 'used-motorcycles.php' ? 'text-primary' : ''; ?>">Motocykle Używane</a>
                    <a href="contact.php" class="text-gray-700 hover:text-primary font-medium whitespace-nowrap <?php echo $current_page == 'contact.php' ? 'text-primary' : ''; ?>">Kontakt</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <a href="<?php echo isLoggedIn() ? 'account.php' : 'login.php'; ?>" class="w-10 h-10 flex items-center justify-center cursor-pointer relative">
                        <i class="ri-user-line <?php echo ($current_page == 'login.php' || $current_page == 'account.php') ? 'text-primary' : 'text-gray-700'; ?> ri-lg"></i>
                    </a>
                    <a href="wishlist.php" class="w-10 h-10 flex items-center justify-center cursor-pointer relative">
                        <i class="ri-heart-line <?php echo $current_page == 'wishlist.php' ? 'text-primary' : 'text-gray-700'; ?> ri-lg"></i>
                        <?php if ($wishlist_count > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-primary text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $wishlist_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="cart.php" id="cartIcon" class="w-10 h-10 flex items-center justify-center cursor-pointer relative">
                        <i class="ri-shopping-cart-line text-gray-700 ri-lg"></i>
                        <?php if ($cart_count > 0): ?>
                        <span id="cartCount" class="absolute -top-1 -right-1 bg-primary text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="md:hidden w-10 h-10 flex items-center justify-center cursor-pointer" id="mobileMenuButton">
                        <i class="ri-menu-line text-gray-700 ri-lg"></i>
                    </div>
                </div>
            </div>
            <div class="mt-3 relative md:hidden">
                <form action="search.php" method="GET">
                    <input type="text" name="query" placeholder="Szukaj produktów..." 
                        class="w-full py-2 px-4 rounded border border-gray-300 focus:outline-none focus:border-primary text-sm">
                    <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 flex items-center justify-center">
                        <i class="ri-search-line text-gray-500"></i>
                    </button>
                </form>
            </div>
            
            <!-- Mobilne menu - domyślnie ukryte -->
            <div id="mobileMenu" class="hidden md:hidden mt-3 bg-white rounded-lg shadow-lg p-4">
                <nav class="flex flex-col space-y-3">
                    <a href="ProductCatalog.php" class="py-2 px-4 rounded-md hover:bg-gray-100 <?php echo $current_page == 'ProductCatalog.php' ? 'bg-gray-100 text-primary' : ''; ?>">Sklep</a>
                    <a href="service.php" class="py-2 px-4 rounded-md hover:bg-gray-100 <?php echo $current_page == 'service.php' ? 'bg-gray-100 text-primary' : ''; ?>">Serwis</a>
                    <a href="used-motorcycles.php" class="py-2 px-4 rounded-md hover:bg-gray-100 <?php echo $current_page == 'used-motorcycles.php' ? 'bg-gray-100 text-primary' : ''; ?>">Motocykle Używane</a>
                    <a href="contact.php" class="py-2 px-4 rounded-md hover:bg-gray-100 <?php echo $current_page == 'contact.php' ? 'bg-gray-100 text-primary' : ''; ?>">Kontakt</a>
                </nav>
            </div>
        </div>
    </header>

    <?php displayMessage(); // Wyświetlanie komunikatów ?>
