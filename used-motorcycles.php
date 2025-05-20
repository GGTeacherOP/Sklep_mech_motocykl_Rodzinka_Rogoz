<?php
// Strona używanych motocykli
$page_title = "Motocykle Używane";
require_once 'includes/config.php';

// Obsługa filtrów
$condition = isset($_GET['condition']) ? sanitize($_GET['condition']) : '';
$brand = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';
$price_min = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 0;
$year_min = isset($_GET['year_min']) ? (int)$_GET['year_min'] : 0;
$year_max = isset($_GET['year_max']) ? (int)$_GET['year_max'] : 0;
$engine_min = isset($_GET['engine_min']) ? (int)$_GET['engine_min'] : 0;
$engine_max = isset($_GET['engine_max']) ? (int)$_GET['engine_max'] : 0;
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'price_asc';

// Tworzenie zapytania SQL
$query = "SELECT m.*, mi.image_path 
          FROM used_motorcycles m 
          LEFT JOIN motorcycle_images mi ON m.id = mi.motorcycle_id AND mi.is_main = 1
          WHERE m.status != 'sold'";

// Dodanie filtrów do zapytania
if (!empty($condition)) {
    $query .= " AND m.condition = '$condition'";
}

if (!empty($brand)) {
    $query .= " AND m.brand = '$brand'";
}

if ($price_min > 0) {
    $query .= " AND m.price >= $price_min";
}

if ($price_max > 0) {
    $query .= " AND m.price <= $price_max";
}

if ($year_min > 0) {
    $query .= " AND m.year >= $year_min";
}

if ($year_max > 0) {
    $query .= " AND m.year <= $year_max";
}

if ($engine_min > 0) {
    $query .= " AND m.engine_capacity >= $engine_min";
}

if ($engine_max > 0) {
    $query .= " AND m.engine_capacity <= $engine_max";
}

// Sortowanie
switch ($sort) {
    case 'price_desc':
        $query .= " ORDER BY m.price DESC";
        break;
    case 'price_asc':
        $query .= " ORDER BY m.price ASC";
        break;
    case 'year_desc':
        $query .= " ORDER BY m.year DESC";
        break;
    case 'year_asc':
        $query .= " ORDER BY m.year ASC";
        break;
    default:
        $query .= " ORDER BY m.price ASC";
}

// Pobieranie motocykli
$motorcycles = [];
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $motorcycles[] = $row;
    }
}

// Pobieranie dostępnych marek dla filtrów
$brands_query = "SELECT DISTINCT brand FROM used_motorcycles ORDER BY brand";
$brands = [];
$brands_result = $conn->query($brands_query);

if ($brands_result && $brands_result->num_rows > 0) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row['brand'];
    }
}

// Statystyki dla filtrów
$stats_query = "SELECT MIN(price) as min_price, MAX(price) as max_price, 
                MIN(year) as min_year, MAX(year) as max_year, 
                MIN(engine_capacity) as min_engine, MAX(engine_capacity) as max_engine 
                FROM used_motorcycles WHERE status != 'sold'";
$stats_result = $conn->query($stats_query);
$stats = null;

if ($stats_result && $stats_result->num_rows > 0) {
    $stats = $stats_result->fetch_assoc();
} else {
    // Domyślne wartości jeśli baza jest pusta
    $stats = [
        'min_price' => 5000,
        'max_price' => 50000,
        'min_year' => 2010,
        'max_year' => 2025,
        'min_engine' => 125,
        'max_engine' => 1200
    ];
}

include 'includes/header.php';
?>

<main>
    <!-- Nagłówek -->
    <section class="relative">
        <div class="relative h-[30vh] md:h-[40vh] bg-gray-900 overflow-hidden">
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('assets/images/service-page-banner.png');">
                <div class="absolute inset-0 bg-black opacity-40"></div>
            </div>
            <div class="absolute inset-0 flex items-center">
                <div class="container mx-auto px-4">
                    <div class="max-w-xl">
                        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Motocykle Używane</h1>
                        <p class="text-xl text-white opacity-90">Szeroki wybór sprawdzonych motocykli używanych</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Główna zawartość -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filtry boczne -->
            <div class="lg:w-1/4 xl:w-1/5">
                <!-- Przycisk filtrów dla urządzeń mobilnych -->
                <div class="lg:hidden mb-4">
                    <button id="filter-toggle" class="w-full bg-white border border-gray-200 rounded py-3 px-4 flex justify-between items-center">
                        <span class="font-medium">Filtry</span>
                        <i class="ri-filter-3-line"></i>
                    </button>
                </div>

                <!-- Panel filtrów -->
                <div id="filters-panel" class="hidden lg:block bg-white rounded-lg shadow-sm p-6 mb-6">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" id="filters-form">
                        <!-- Stan -->
                        <div class="mb-6">
                            <h3 class="font-semibold text-lg mb-4">Stan</h3>
                            <div class="space-y-3">
                                <label class="custom-checkbox">
                                    <input type="radio" name="condition" value="" <?php echo empty($condition) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Wszystkie
                                </label>
                                <label class="custom-checkbox">
                                    <input type="radio" name="condition" value="excellent" <?php echo $condition == 'excellent' ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Doskonały
                                </label>
                                <label class="custom-checkbox">
                                    <input type="radio" name="condition" value="very_good" <?php echo $condition == 'very_good' ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Bardzo dobry
                                </label>
                                <label class="custom-checkbox">
                                    <input type="radio" name="condition" value="good" <?php echo $condition == 'good' ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Dobry
                                </label>
                                <label class="custom-checkbox">
                                    <input type="radio" name="condition" value="average" <?php echo $condition == 'average' ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Średni
                                </label>
                            </div>
                        </div>

                        <!-- Marka -->
                        <div class="mb-6 border-t pt-6">
                            <h3 class="font-semibold text-lg mb-4">Marka</h3>
                            <div class="space-y-3">
                                <label class="custom-checkbox">
                                    <input type="radio" name="brand" value="" <?php echo empty($brand) ? 'checked' : ''; ?>>
                                    <span class="checkmark"></span>
                                    Wszystkie
                                </label>
                                
                                <?php if (!empty($brands)): ?>
                                    <?php foreach ($brands as $brand_name): ?>
                                    <label class="custom-checkbox">
                                        <input type="radio" name="brand" value="<?php echo $brand_name; ?>" <?php echo $brand == $brand_name ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <?php echo $brand_name; ?>
                                    </label>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Domyślne marki jeśli baza jest pusta -->
                                    <?php 
                                    $default_brands = ['Honda', 'Yamaha', 'Suzuki', 'Kawasaki', 'BMW', 'Ducati', 'Harley-Davidson'];
                                    foreach ($default_brands as $brand_name):
                                    ?>
                                    <label class="custom-checkbox">
                                        <input type="radio" name="brand" value="<?php echo $brand_name; ?>" <?php echo $brand == $brand_name ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                        <?php echo $brand_name; ?>
                                    </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Cena -->
                        <div class="mb-6 border-t pt-6">
                            <h3 class="font-semibold text-lg mb-4">Cena (zł)</h3>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="text-sm text-gray-600">Od</label>
                                    <input type="number" name="price_min" value="<?php echo $price_min ?: ''; ?>" placeholder="<?php echo number_format($stats['min_price']); ?>" class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Do</label>
                                    <input type="number" name="price_max" value="<?php echo $price_max ?: ''; ?>" placeholder="<?php echo number_format($stats['max_price']); ?>" class="w-full p-2 border rounded">
                                </div>
                            </div>
                        </div>

                        <!-- Rok produkcji -->
                        <div class="mb-6 border-t pt-6">
                            <h3 class="font-semibold text-lg mb-4">Rok produkcji</h3>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="text-sm text-gray-600">Od</label>
                                    <input type="number" name="year_min" value="<?php echo $year_min ?: ''; ?>" placeholder="<?php echo $stats['min_year']; ?>" class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Do</label>
                                    <input type="number" name="year_max" value="<?php echo $year_max ?: ''; ?>" placeholder="<?php echo $stats['max_year']; ?>" class="w-full p-2 border rounded">
                                </div>
                            </div>
                        </div>

                        <!-- Pojemność silnika -->
                        <div class="mb-6 border-t pt-6">
                            <h3 class="font-semibold text-lg mb-4">Pojemność silnika (cm³)</h3>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="text-sm text-gray-600">Od</label>
                                    <input type="number" name="engine_min" value="<?php echo $engine_min ?: ''; ?>" placeholder="<?php echo $stats['min_engine']; ?>" class="w-full p-2 border rounded">
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Do</label>
                                    <input type="number" name="engine_max" value="<?php echo $engine_max ?: ''; ?>" placeholder="<?php echo $stats['max_engine']; ?>" class="w-full p-2 border rounded">
                                </div>
                            </div>
                        </div>

                        <!-- Przyciski formularza -->
                        <div class="flex space-x-4 pt-4 border-t">
                            <button type="submit" class="flex-1 bg-primary text-white py-2 rounded-button font-medium hover:bg-opacity-90 transition">
                                Filtruj
                            </button>
                            <button type="reset" id="reset-filters" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-button font-medium hover:bg-gray-300 transition">
                                Resetuj
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista motocykli -->
            <div class="lg:w-3/4 xl:w-4/5">
                <!-- Górny pasek z sortowaniem i licznikiem -->
                <div class="bg-white p-4 rounded-lg shadow-sm mb-6 flex flex-col sm:flex-row justify-between items-center">
                    <div class="mb-4 sm:mb-0">
                        <span class="text-gray-600">Znaleziono: <span class="font-semibold"><?php echo count($motorcycles); ?></span> motocykli</span>
                    </div>
                    <div class="flex items-center">
                        <label class="text-gray-600 mr-2">Sortuj według:</label>
                        <select id="sort-select" class="border rounded p-2">
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Cena: od najniższej</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Cena: od najwyższej</option>
                            <option value="year_desc" <?php echo $sort == 'year_desc' ? 'selected' : ''; ?>>Rok: od najnowszych</option>
                            <option value="year_asc" <?php echo $sort == 'year_asc' ? 'selected' : ''; ?>>Rok: od najstarszych</option>
                        </select>
                    </div>
                </div>

                <!-- Siatka motocykli -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    if (!empty($motorcycles)): 
                        foreach ($motorcycles as $motorcycle): 
                            $image = $motorcycle['image_path'] ?? 'assets/images/motorcycle-placeholder.jpg';
                            
                            // Konwersja wartości warunku
                            $condition_text = '';
                            switch ($motorcycle['condition']) {
                                case 'excellent': $condition_text = 'Doskonały'; break;
                                case 'very_good': $condition_text = 'Bardzo dobry'; break;
                                case 'good': $condition_text = 'Dobry'; break;
                                case 'average': $condition_text = 'Średni'; break;
                                case 'poor': $condition_text = 'Słaby'; break;
                                default: $condition_text = 'Nieznany';
                            }
                            
                            // Konwersja statusu
                            $status_class = 'bg-green-500';
                            $status_text = 'Dostępny';
                            
                            if ($motorcycle['status'] == 'reserved') {
                                $status_class = 'bg-yellow-500';
                                $status_text = 'Zarezerwowany';
                            } elseif ($motorcycle['status'] == 'sold') {
                                $status_class = 'bg-red-500';
                                $status_text = 'Sprzedany';
                            }
                    ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden group">
                        <a href="motorcycle.php?id=<?php echo $motorcycle['id']; ?>" class="block relative">
                            <div class="absolute top-3 right-3 <?php echo $status_class; ?> text-white text-xs font-semibold px-2 py-1 rounded">
                                <?php echo $status_text; ?>
                            </div>
                            <img src="<?php echo $image; ?>" alt="<?php echo $motorcycle['title']; ?>" class="w-full h-48 object-cover">
                        </a>
                        
                        <div class="p-4">
                            <a href="motorcycle.php?id=<?php echo $motorcycle['id']; ?>" class="block mb-2">
                                <h3 class="font-semibold text-gray-800 group-hover:text-primary transition"><?php echo $motorcycle['title']; ?></h3>
                            </a>
                            
                            <div class="text-gray-500 text-sm mb-3">
                                <?php echo $motorcycle['brand']; ?> | <?php echo $motorcycle['model']; ?> | <?php echo $condition_text; ?>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded"><?php echo $motorcycle['year']; ?></span>
                                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded"><?php echo number_format($motorcycle['mileage']); ?> km</span>
                                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded"><?php echo $motorcycle['engine_capacity']; ?> cm³</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="text-primary font-bold text-lg"><?php echo number_format($motorcycle['price'], 2, ',', ' '); ?> zł</div>
                                <a href="motorcycle.php?id=<?php echo $motorcycle['id']; ?>" class="bg-primary text-white px-4 py-2 rounded-button text-sm hover:bg-opacity-90 transition">
                                    Szczegóły
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endforeach; 
                    else: 
                        // Jeśli nie ma motocykli lub baza jest pusta, wyświetl przykładowe
                        $default_motorcycles = [
                            [
                                'id' => 1,
                                'title' => 'Honda CBR 600RR',
                                'brand' => 'Honda',
                                'model' => 'CBR 600RR',
                                'year' => 2018,
                                'mileage' => 25000,
                                'engine_capacity' => 600,
                                'price' => 29900.00,
                                'condition' => 'very_good',
                                'status' => 'available',
                                'image_path' => 'https://readdy.ai/api/search-image?query=Honda%20CBR%20600RR%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1'
                            ],
                            [
                                'id' => 2,
                                'title' => 'BMW R 1250 GS',
                                'brand' => 'BMW',
                                'model' => 'R 1250 GS',
                                'year' => 2020,
                                'mileage' => 15000,
                                'engine_capacity' => 1250,
                                'price' => 59900.00,
                                'condition' => 'excellent',
                                'status' => 'available',
                                'image_path' => 'https://readdy.ai/api/search-image?query=BMW%20R%201250%20GS%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1'
                            ],
                            [
                                'id' => 3,
                                'title' => 'Yamaha MT-07',
                                'brand' => 'Yamaha',
                                'model' => 'MT-07',
                                'year' => 2019,
                                'mileage' => 12500,
                                'engine_capacity' => 700,
                                'price' => 32900.00,
                                'condition' => 'very_good',
                                'status' => 'available',
                                'image_path' => 'https://readdy.ai/api/search-image?query=Yamaha%20MT-07%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1'
                            ],
                            [
                                'id' => 4,
                                'title' => 'Ducati Panigale V4',
                                'brand' => 'Ducati',
                                'model' => 'Panigale V4',
                                'year' => 2021,
                                'mileage' => 8000,
                                'engine_capacity' => 1103,
                                'price' => 89900.00,
                                'condition' => 'excellent',
                                'status' => 'reserved',
                                'image_path' => 'https://readdy.ai/api/search-image?query=Ducati%20Panigale%20V4%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1'
                            ],
                            [
                                'id' => 5,
                                'title' => 'Kawasaki Z900',
                                'brand' => 'Kawasaki',
                                'model' => 'Z900',
                                'year' => 2020,
                                'mileage' => 18500,
                                'engine_capacity' => 900,
                                'price' => 42900.00,
                                'condition' => 'good',
                                'status' => 'available',
                                'image_path' => 'https://readdy.ai/api/search-image?query=Kawasaki%20Z900%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1'
                            ],
                            [
                                'id' => 6,
                                'title' => 'Harley-Davidson Sportster 883',
                                'brand' => 'Harley-Davidson',
                                'model' => 'Sportster 883',
                                'year' => 2017,
                                'mileage' => 22000,
                                'engine_capacity' => 883,
                                'price' => 38900.00,
                                'condition' => 'good',
                                'status' => 'available',
                                'image_path' => 'https://readdy.ai/api/search-image?query=Harley-Davidson%20Sportster%20883%20motorcycle%20side%20view%20clean%20background&width=400&height=300&seq=1'
                            ]
                        ];
                        
                        foreach ($default_motorcycles as $motorcycle): 
                            $image = $motorcycle['image_path'] ?? 'assets/images/motorcycle-placeholder.jpg';
                            
                            // Konwersja wartości warunku
                            $condition_text = '';
                            switch ($motorcycle['condition']) {
                                case 'excellent': $condition_text = 'Doskonały'; break;
                                case 'very_good': $condition_text = 'Bardzo dobry'; break;
                                case 'good': $condition_text = 'Dobry'; break;
                                case 'average': $condition_text = 'Średni'; break;
                                case 'poor': $condition_text = 'Słaby'; break;
                                default: $condition_text = 'Nieznany';
                            }
                            
                            // Konwersja statusu
                            $status_class = 'bg-green-500';
                            $status_text = 'Dostępny';
                            
                            if ($motorcycle['status'] == 'reserved') {
                                $status_class = 'bg-yellow-500';
                                $status_text = 'Zarezerwowany';
                            } elseif ($motorcycle['status'] == 'sold') {
                                $status_class = 'bg-red-500';
                                $status_text = 'Sprzedany';
                            }
                    ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden group">
                        <a href="motorcycle.php?id=<?php echo $motorcycle['id']; ?>" class="block relative">
                            <div class="absolute top-3 right-3 <?php echo $status_class; ?> text-white text-xs font-semibold px-2 py-1 rounded">
                                <?php echo $status_text; ?>
                            </div>
                            <img src="<?php echo $image; ?>" alt="<?php echo $motorcycle['title']; ?>" class="w-full h-48 object-cover">
                        </a>
                        
                        <div class="p-4">
                            <a href="motorcycle.php?id=<?php echo $motorcycle['id']; ?>" class="block mb-2">
                                <h3 class="font-semibold text-gray-800 group-hover:text-primary transition"><?php echo $motorcycle['title']; ?></h3>
                            </a>
                            
                            <div class="text-gray-500 text-sm mb-3">
                                <?php echo $motorcycle['brand']; ?> | <?php echo $motorcycle['model']; ?> | <?php echo $condition_text; ?>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mb-3">
                                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded"><?php echo $motorcycle['year']; ?></span>
                                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded"><?php echo number_format($motorcycle['mileage']); ?> km</span>
                                <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded"><?php echo $motorcycle['engine_capacity']; ?> cm³</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="text-primary font-bold text-lg"><?php echo number_format($motorcycle['price'], 2, ',', ' '); ?> zł</div>
                                <a href="motorcycle.php?id=<?php echo $motorcycle['id']; ?>" class="bg-primary text-white px-4 py-2 rounded-button text-sm hover:bg-opacity-90 transition">
                                    Szczegóły
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// Dodanie JS dla strony motocykli
$extra_js = <<<EOT
<script>
    // Obsługa filtrów na urządzeniach mobilnych
    document.addEventListener('DOMContentLoaded', function() {
        const filterToggle = document.getElementById('filter-toggle');
        const filtersPanel = document.getElementById('filters-panel');
        
        if (filterToggle && filtersPanel) {
            filterToggle.addEventListener('click', function() {
                filtersPanel.classList.toggle('hidden');
            });
        }
        
        // Obsługa sortowania
        const sortSelect = document.getElementById('sort-select');
        sortSelect.addEventListener('change', function() {
            // Dodaj wartość sortowania do formularza filtrów i wyślij go
            const form = document.getElementById('filters-form');
            
            // Stwórz ukryte pole dla sortowania jeśli nie istnieje
            let sortInput = form.querySelector('input[name="sort"]');
            if (!sortInput) {
                sortInput = document.createElement('input');
                sortInput.type = 'hidden';
                sortInput.name = 'sort';
                form.appendChild(sortInput);
            }
            
            // Ustaw wartość sortowania i wyślij formularz
            sortInput.value = this.value;
            form.submit();
        });
        
        // Obsługa przycisku resetowania filtrów
        const resetButton = document.getElementById('reset-filters');
        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'used-motorcycles.php';
        });
    });
</script>
EOT;

include 'includes/footer.php';
?>
