<?php
// Strona główna sklepu
$page_title = "Strona główna";
require_once 'includes/config.php';

// Pobieranie polecanych produktów
$featured_products_query = "SELECT p.*, pi.image_path, b.name as brand_name, c.name as category_name 
                           FROM products p 
                           LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                           LEFT JOIN brands b ON p.brand_id = b.id
                           LEFT JOIN categories c ON p.category_id = c.id
                           WHERE p.featured = 1 
                           ORDER BY p.created_at DESC 
                           LIMIT 8";

$featured_products = [];
$featured_result = $conn->query($featured_products_query);

if ($featured_result && $featured_result->num_rows > 0) {
    while ($row = $featured_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// Pobieranie polecanych motocykli używanych
$featured_motorcycles_query = "SELECT m.*, mi.image_path 
                              FROM used_motorcycles m 
                              LEFT JOIN motorcycle_images mi ON m.id = mi.motorcycle_id AND mi.is_main = 1
                              WHERE m.featured = 1 
                              ORDER BY m.created_at DESC 
                              LIMIT 4";

$featured_motorcycles = [];
$motorcycles_result = $conn->query($featured_motorcycles_query);

if ($motorcycles_result && $motorcycles_result->num_rows > 0) {
    while ($row = $motorcycles_result->fetch_assoc()) {
        $featured_motorcycles[] = $row;
    }
}

// Pobieranie mechaników
$mechanics_query = "SELECT * FROM mechanics WHERE status = 'active' LIMIT 3";
$mechanics = [];
$mechanics_result = $conn->query($mechanics_query);

if ($mechanics_result && $mechanics_result->num_rows > 0) {
    while ($row = $mechanics_result->fetch_assoc()) {
        $mechanics[] = $row;
    }
}

include 'includes/header.php';
?>

<main>
    <!-- Hero section -->
    <section class="relative">
        <!-- Hero Slider -->
        <div class="relative h-[50vh] md:h-[70vh] bg-gray-900 overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://readdy.ai/api/search-image?query=Motorcycle%20shop%20with%20multiple%20motorcycles%20on%20display%2C%20professional%2C%20clean%2C%20well-lit%20showroom&width=1800&height=1000&orientation=landscape');">
                <div class="absolute inset-0 bg-black opacity-40"></div>
            </div>
            <div class="absolute inset-0 flex items-center">
                <div class="container mx-auto px-4">
                    <div class="max-w-xl">
                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4">Twój kompleksowy sklep motocyklowy</h1>
                        <p class="text-xl text-white opacity-90 mb-8">Części, akcesoria, serwis i motocykle używane w jednym miejscu</p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="ProductCatalog.php" class="bg-primary text-white px-8 py-3 rounded-button font-medium hover:bg-opacity-90 transition text-center">
                                Przejdź do sklepu
                            </a>
                            <a href="service.php" class="bg-white text-gray-800 px-8 py-3 rounded-button font-medium hover:bg-gray-100 transition text-center">
                                Umów serwis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kategorie -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Nasze kategorie</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php
                // Pobieranie kategorii
                $categories_query = "SELECT * FROM categories ORDER BY name LIMIT 6";
                $categories_result = $conn->query($categories_query);
                
                if ($categories_result && $categories_result->num_rows > 0) {
                    while ($category = $categories_result->fetch_assoc()) {
                        echo '<a href="ProductCatalog.php?category=' . $category['slug'] . '" class="bg-white rounded-lg shadow-sm p-6 text-center hover:shadow-md transition group">';
                        echo '<div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition">';
                        
                        // Wybór ikony w zależności od kategorii
                        $icon = 'ri-shopping-bag-line';
                        switch ($category['slug']) {
                            case 'kaski': $icon = 'ri-bold'; break;
                            case 'odziez': $icon = 'ri-t-shirt-line'; break;
                            case 'czesci': $icon = 'ri-tools-line'; break;
                            case 'oleje': $icon = 'ri-oil-line'; break;
                            case 'akumulatory': $icon = 'ri-battery-2-charge-line'; break;
                            case 'akcesoria': $icon = 'ri-steering-2-line'; break;
                        }
                        
                        echo '<i class="' . $icon . ' ri-xl"></i>';
                        echo '</div>';
                        echo '<h3 class="font-semibold text-gray-800">' . $category['name'] . '</h3>';
                        echo '</a>';
                    }
                } else {
                    // Wyświetl domyślne kategorie, jeśli nie ma danych w bazie
                    $default_categories = [
                        ['name' => 'Kaski', 'slug' => 'kaski', 'icon' => 'ri-bold'],
                        ['name' => 'Odzież', 'slug' => 'odziez', 'icon' => 'ri-t-shirt-line'],
                        ['name' => 'Części', 'slug' => 'czesci', 'icon' => 'ri-tools-line'],
                        ['name' => 'Oleje', 'slug' => 'oleje', 'icon' => 'ri-oil-line'],
                        ['name' => 'Akumulatory', 'slug' => 'akumulatory', 'icon' => 'ri-battery-2-charge-line'],
                        ['name' => 'Akcesoria', 'slug' => 'akcesoria', 'icon' => 'ri-steering-2-line']
                    ];
                    
                    foreach ($default_categories as $category) {
                        echo '<a href="ProductCatalog.php?category=' . $category['slug'] . '" class="bg-white rounded-lg shadow-sm p-6 text-center hover:shadow-md transition group">';
                        echo '<div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition">';
                        echo '<i class="' . $category['icon'] . ' ri-xl"></i>';
                        echo '</div>';
                        echo '<h3 class="font-semibold text-gray-800">' . $category['name'] . '</h3>';
                        echo '</a>';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Polecane produkty -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-bold">Polecane produkty</h2>
                <a href="ProductCatalog.php" class="text-primary font-medium hover:underline flex items-center">
                    Zobacz wszystkie
                    <i class="ri-arrow-right-line ml-2"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                if (!empty($featured_products)) {
                    foreach ($featured_products as $product) {
                        $image = $product['image_path'] ?? 'assets/images/placeholder.jpg';
                        
                        // Oblicz procent rabatu jeśli jest
                        $discount_percentage = '';
                        if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
                            $discount_percentage = round(100 - ($product['sale_price'] / $product['price'] * 100));
                        }
                        
                        echo '<div class="bg-white rounded-lg shadow-sm overflow-hidden group">';
                        echo '<a href="product.php?slug=' . $product['slug'] . '" class="block relative">';
                        
                        // Znacznik promocji
                        if (!empty($discount_percentage)) {
                            echo '<div class="absolute top-3 left-3 bg-primary text-white text-xs font-semibold px-2 py-1 rounded">';
                            echo 'Promocja -' . $discount_percentage . '%';
                            echo '</div>';
                        }
                        
                        echo '<img src="' . $image . '" alt="' . $product['name'] . '" class="w-full h-48 object-cover">';
                        echo '</a>';
                        
                        echo '<div class="p-4">';
                        echo '<div class="text-xs text-gray-500 mb-1">' . ($product['brand_name'] ?? '') . ' | ' . ($product['category_name'] ?? '') . '</div>';
                        echo '<a href="product.php?slug=' . $product['slug'] . '" class="block mb-2">';
                        echo '<h3 class="font-semibold text-gray-800 group-hover:text-primary transition line-clamp-2">' . $product['name'] . '</h3>';
                        echo '</a>';
                        
                        echo '<div class="flex items-center mb-3">';
                        
                        // Pobierz średnią ocenę dla produktu
                        $rating_query = "SELECT AVG(rating) as avg_rating FROM product_reviews WHERE product_id = " . $product['id'] . " AND status = 'approved'";
                        $rating_result = $conn->query($rating_query);
                        $rating = 0;
                        
                        if ($rating_result && $rating_result->num_rows > 0) {
                            $rating_row = $rating_result->fetch_assoc();
                            $rating = round($rating_row['avg_rating'], 1);
                        }
                        
                        // Wyświetl gwiazdki
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="ri-star-fill text-yellow-400"></i>';
                            } else if ($i - 0.5 <= $rating) {
                                echo '<i class="ri-star-half-fill text-yellow-400"></i>';
                            } else {
                                echo '<i class="ri-star-line text-yellow-400"></i>';
                            }
                        }
                        
                        echo '</div>';
                        
                        echo '<div class="flex justify-between items-center">';
                        echo '<div>';
                        
                        if (!empty($product['sale_price'])) {
                            echo '<span class="text-primary font-bold">' . number_format($product['sale_price'], 2, ',', ' ') . ' zł</span>';
                            echo '<span class="text-gray-400 line-through text-sm ml-2">' . number_format($product['price'], 2, ',', ' ') . ' zł</span>';
                        } else {
                            echo '<span class="text-primary font-bold">' . number_format($product['price'], 2, ',', ' ') . ' zł</span>';
                        }
                        
                        echo '</div>';
                        echo '<button onclick="addToCart(' . $product['id'] . ')" class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-primary hover:text-white transition">';
                        echo '<i class="ri-shopping-cart-line"></i>';
                        echo '</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="col-span-4 text-center py-8">';
                    echo '<p class="text-gray-500">Brak polecanych produktów.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Banner serwisu -->
    <section class="py-16 relative">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('https://readdy.ai/api/search-image?query=Motorcycle%20repair%20workshop%2C%20mechanic%20working%20on%20motorcycle%2C%20professional%20tools%2C%20clean%20workspace&width=1800&height=600&orientation=landscape');">
            <div class="absolute inset-0 bg-primary opacity-75"></div>
        </div>
        <div class="container mx-auto px-4 relative">
            <div class="max-w-xl text-white">
                <h2 class="text-3xl font-bold mb-4">Profesjonalny serwis motocyklowy</h2>
                <p class="text-lg opacity-90 mb-6">Nasi wykwalifikowani mechanicy zapewnią Twojemu motocyklowi najlepszą opiekę. Oferujemy kompleksowe usługi serwisowe, przeglądy i naprawy.</p>
                <a href="service.php" class="inline-block bg-white text-primary px-8 py-3 rounded-button font-medium hover:bg-gray-100 transition">
                    Umów wizytę
                </a>
            </div>
        </div>
    </section>

    <!-- Motocykle używane -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-bold">Motocykle używane</h2>
                <a href="used-motorcycles.php" class="text-primary font-medium hover:underline flex items-center">
                    Zobacz wszystkie
                    <i class="ri-arrow-right-line ml-2"></i>
                </a>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                if (!empty($featured_motorcycles)) {
                    foreach ($featured_motorcycles as $motorcycle) {
                        $image = $motorcycle['image_path'] ?? 'assets/images/motorcycle-placeholder.jpg';
                        
                        echo '<div class="bg-white rounded-lg shadow-sm overflow-hidden group">';
                        echo '<a href="motorcycle.php?id=' . $motorcycle['id'] . '" class="block relative">';
                        
                        // Znacznik statusu
                        $status_class = 'bg-green-500';
                        $status_text = 'Dostępny';
                        
                        if ($motorcycle['status'] == 'reserved') {
                            $status_class = 'bg-yellow-500';
                            $status_text = 'Zarezerwowany';
                        } elseif ($motorcycle['status'] == 'sold') {
                            $status_class = 'bg-red-500';
                            $status_text = 'Sprzedany';
                        }
                        
                        echo '<div class="absolute top-3 right-3 ' . $status_class . ' text-white text-xs font-semibold px-2 py-1 rounded">';
                        echo $status_text;
                        echo '</div>';
                        
                        echo '<img src="' . $image . '" alt="' . $motorcycle['title'] . '" class="w-full h-48 object-cover">';
                        echo '</a>';
                        
                        echo '<div class="p-4">';
                        echo '<a href="motorcycle.php?id=' . $motorcycle['id'] . '" class="block mb-2">';
                        echo '<h3 class="font-semibold text-gray-800 group-hover:text-primary transition">' . $motorcycle['title'] . '</h3>';
                        echo '</a>';
                        
                        echo '<div class="flex flex-wrap gap-2 mb-3">';
                        echo '<span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">' . $motorcycle['year'] . '</span>';
                        echo '<span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">' . number_format($motorcycle['mileage']) . ' km</span>';
                        echo '<span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">' . $motorcycle['engine_capacity'] . ' cm³</span>';
                        echo '</div>';
                        
                        echo '<div class="text-primary font-bold text-lg">' . number_format($motorcycle['price'], 2, ',', ' ') . ' zł</div>';
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="col-span-4 text-center py-8">';
                    echo '<p class="text-gray-500">Brak motocykli używanych.</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Mechanicy -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Nasi mechanicy</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                if (!empty($mechanics)) {
                    foreach ($mechanics as $mechanic) {
                        $image = $mechanic['image_path'] ?? 'https://readdy.ai/api/search-image?query=Professional%20motorcycle%20mechanic%20in%20uniform%2C%20confident%20pose%2C%20clean%20background&width=100&height=100&seq=' . $mechanic['id'] . '&orientation=squarish';
                        
                        echo '<div class="bg-white rounded-lg shadow-sm p-6">';
                        echo '<div class="flex items-center mb-4">';
                        echo '<img src="' . $image . '" alt="' . $mechanic['name'] . '" class="w-20 h-20 rounded-full object-cover mr-4">';
                        echo '<div>';
                        echo '<h3 class="text-xl font-bold">' . $mechanic['name'] . '</h3>';
                        echo '<p class="text-gray-600">' . $mechanic['specialization'] . '</p>';
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div class="flex text-yellow-400 mb-4">';
                        
                        $rating = $mechanic['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="ri-star-fill"></i>';
                            } else if ($i - 0.5 <= $rating) {
                                echo '<i class="ri-star-half-fill"></i>';
                            } else {
                                echo '<i class="ri-star-line"></i>';
                            }
                        }
                        
                        echo '<span class="text-gray-600 ml-2">(' . $rating . '/5)</span>';
                        echo '</div>';
                        
                        echo '<p class="text-gray-600 mb-4">' . $mechanic['description'] . '</p>';
                        echo '<a href="service.php?mechanic=' . $mechanic['id'] . '" class="block w-full bg-primary text-white py-2 rounded-button text-center font-medium hover:bg-opacity-90 transition">';
                        echo 'Umów wizytę';
                        echo '</a>';
                        echo '</div>';
                    }
                } else {
                    // Domyślni mechanicy
                    $default_mechanics = [
                        [
                            'id' => 1,
                            'name' => 'Jan Kowalski',
                            'specialization' => 'Specjalista Honda, Yamaha',
                            'rating' => 4.5,
                            'description' => 'Doświadczony mechanik z 15-letnim stażem. Specjalizuje się w motocyklach japońskich.'
                        ],
                        [
                            'id' => 2,
                            'name' => 'Piotr Nowak',
                            'specialization' => 'Specjalista BMW, Ducati',
                            'rating' => 5.0,
                            'description' => 'Ekspert w motocyklach europejskich. Certyfikowany mechanik BMW i Ducati.'
                        ],
                        [
                            'id' => 3,
                            'name' => 'Anna Wiśniewska',
                            'specialization' => 'Specjalista Suzuki, Kawasaki',
                            'rating' => 4.0,
                            'description' => 'Młoda, ambitna mechanik z pasją do motocykli sportowych.'
                        ]
                    ];
                    
                    foreach ($default_mechanics as $mechanic) {
                        $image = 'https://readdy.ai/api/search-image?query=Professional%20motorcycle%20mechanic%20in%20uniform%2C%20confident%20pose%2C%20clean%20background&width=100&height=100&seq=' . $mechanic['id'] . '&orientation=squarish';
                        
                        echo '<div class="bg-white rounded-lg shadow-sm p-6">';
                        echo '<div class="flex items-center mb-4">';
                        echo '<img src="' . $image . '" alt="' . $mechanic['name'] . '" class="w-20 h-20 rounded-full object-cover mr-4">';
                        echo '<div>';
                        echo '<h3 class="text-xl font-bold">' . $mechanic['name'] . '</h3>';
                        echo '<p class="text-gray-600">' . $mechanic['specialization'] . '</p>';
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div class="flex text-yellow-400 mb-4">';
                        
                        $rating = $mechanic['rating'];
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="ri-star-fill"></i>';
                            } else if ($i - 0.5 <= $rating) {
                                echo '<i class="ri-star-half-fill"></i>';
                            } else {
                                echo '<i class="ri-star-line"></i>';
                            }
                        }
                        
                        echo '<span class="text-gray-600 ml-2">(' . $rating . '/5)</span>';
                        echo '</div>';
                        
                        echo '<p class="text-gray-600 mb-4">' . $mechanic['description'] . '</p>';
                        echo '<a href="service.php?mechanic=' . $mechanic['id'] . '" class="block w-full bg-primary text-white py-2 rounded-button text-center font-medium hover:bg-opacity-90 transition">';
                        echo 'Umów wizytę';
                        echo '</a>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-16 bg-gray-900 text-white">
        <div class="container mx-auto px-4">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl font-bold mb-4">Zapisz się do newslettera</h2>
                <p class="text-gray-300 mb-8">Otrzymuj powiadomienia o promocjach, nowych produktach i poradach dotyczących Twojego motocykla.</p>
                
                <form action="newsletter-signup.php" method="POST" class="flex flex-col sm:flex-row gap-4">
                    <input type="email" name="email" placeholder="Twój adres email" required
                        class="flex-grow py-3 px-4 rounded-button bg-gray-800 border border-gray-700 text-white placeholder-gray-400 focus:outline-none focus:border-primary">
                    <button type="submit" class="bg-primary text-white py-3 px-8 rounded-button font-medium hover:bg-opacity-90 transition">
                        Zapisz się
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php
// Skrypt JS do obsługi dodawania do koszyka i inne funkcje
$extra_js = <<<EOT
<script>
function addToCart(productId) {
    fetch('cart-actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add&product_id=' + productId + '&quantity=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Aktualizacja liczby produktów w koszyku
            const cartCount = document.getElementById('cartCount');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
                cartCount.classList.remove('hidden');
            }
            
            alert('Produkt został dodany do koszyka');
        } else {
            alert('Wystąpił błąd: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Wystąpił błąd podczas dodawania produktu do koszyka');
    });
}
</script>
EOT;

include 'includes/footer.php';
?>
