<?php
// Włączenie wyświetlania błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

// Resetowanie auto-inkrementu w tabeli products
$reset_query = "ALTER TABLE products AUTO_INCREMENT = 1";
if (!$conn->query($reset_query)) {
    echo "Błąd podczas resetowania auto-inkrementu: " . $conn->error . "<br>";
} else {
    echo "Auto-inkrement został zresetowany.<br>";
}

// Usunięcie istniejących produktów
$delete_query = "DELETE FROM products";
if (!$conn->query($delete_query)) {
    echo "Błąd podczas usuwania istniejących produktów: " . $conn->error . "<br>";
} else {
    echo "Istniejące produkty zostały usunięte.<br>";
}

// Dodawanie nowych marek
$brands = [
    ['name' => 'AGV', 'slug' => 'agv'],
    ['name' => 'Castrol', 'slug' => 'castrol'],
    ['name' => 'DID', 'slug' => 'did'],
    ['name' => 'Galfer', 'slug' => 'galfer'],
    ['name' => 'Shido', 'slug' => 'shido'],
    ['name' => 'Michelin', 'slug' => 'michelin']
];

foreach ($brands as $brand) {
    // Sprawdzenie czy marka już istnieje
    $check_query = "SELECT id FROM brands WHERE slug = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $brand['slug']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Dodawanie nowej marki
        $insert_query = "INSERT INTO brands (name, slug) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $brand['name'], $brand['slug']);
        
        if ($stmt->execute()) {
            echo "Dodano markę: {$brand['name']}<br>";
        } else {
            echo "Błąd podczas dodawania marki {$brand['name']}: " . $stmt->error . "<br>";
        }
    } else {
        echo "Marka {$brand['name']} już istnieje.<br>";
    }
}

// Sprawdzenie istniejących kategorii
$categories_query = "SELECT id, name FROM categories ORDER BY id";
$categories_result = $conn->query($categories_query);
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[$row['id']] = $row['name'];
}

echo "Dostępne kategorie:<br>";
foreach ($categories as $id => $name) {
    echo "ID: $id - $name<br>";
}

// Sprawdzenie istniejących marek
$brands_query = "SELECT id, name FROM brands ORDER BY id";
$brands_result = $conn->query($brands_query);
$brands = [];
while ($row = $brands_result->fetch_assoc()) {
    $brands[$row['id']] = $row['name'];
}

echo "<br>Dostępne marki:<br>";
foreach ($brands as $id => $name) {
    echo "ID: $id - $name<br>";
}

// Sprawdzenie istniejących ID produktów
$products_query = "SELECT id FROM products ORDER BY id";
$products_result = $conn->query($products_query);
$existing_ids = [];
while ($row = $products_result->fetch_assoc()) {
    $existing_ids[] = $row['id'];
}

echo "<br>Istniejące ID produktów:<br>";
echo implode(", ", $existing_ids) . "<br><br>";

// Funkcja do generowania unikalnego sluga
function generateUniqueSlug($conn, $name, $id = null) {
    $base_slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
    $slug = $base_slug;
    $counter = 1;
    
    while (true) {
        $query = "SELECT id FROM products WHERE slug = ?";
        if ($id) {
            $query .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($query);
        if ($id) {
            $stmt->bind_param("si", $slug, $id);
        } else {
            $stmt->bind_param("s", $slug);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            break;
        }
        
        $slug = $base_slug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

// Przykładowe produkty
$products = [
    [
        'name' => 'Kask motocyklowy AGV K6',
        'description' => 'Kask motocyklowy AGV K6 to nowoczesny kask sportowy z kompozytu włókna węglowego. Posiada system wentylacji, wyjmowaną wkładkę i jest kompatybilny z systemem komunikacji.',
        'short_description' => 'Kask motocyklowy AGV K6 - nowoczesność i bezpieczeństwo',
        'price' => 1499.99,
        'sale_price' => 1399.99,
        'stock' => 25,
        'sku' => 'AGV-K6-001',
        'featured' => 1,
        'status' => 'published',
        'category_id' => 1, // Kaski
        'brand_id' => 13 // AGV
    ],
    [
        'name' => 'Rękawice motocyklowe Dainese 4 Stroke Evo',
        'description' => 'Rękawice motocyklowe Dainese 4 Stroke Evo to uniwersalne rękawice sportowe. Wykonane ze skóry bydlęcej z dodatkowymi wzmocnieniami, posiadają system wentylacji i ochronę kostek.',
        'short_description' => 'Rękawice motocyklowe Dainese 4 Stroke Evo - uniwersalna ochrona',
        'price' => 349.99,
        'sale_price' => null,
        'stock' => 40,
        'sku' => 'DAI-4SE-001',
        'featured' => 0,
        'status' => 'published',
        'category_id' => 2, // Odzież
        'brand_id' => 2 // Dainese
    ],
    [
        'name' => 'Kurtka motocyklowa Alpinestars GP Plus R',
        'description' => 'Kurtka motocyklowa Alpinestars GP Plus R to profesjonalna kurtka sportowa. Wykonana z materiału 600D, posiada system wentylacji, wymienne ochraniacze i jest kompatybilna z systemem Airbag.',
        'short_description' => 'Kurtka motocyklowa Alpinestars GP Plus R - profesjonalna ochrona',
        'price' => 1999.99,
        'sale_price' => 1899.99,
        'stock' => 15,
        'sku' => 'ALP-GPPR-001',
        'featured' => 1,
        'status' => 'published',
        'category_id' => 2, // Odzież
        'brand_id' => 1 // Alpinestars
    ],
    [
        'name' => 'Spodnie motocyklowe Dainese Super Speed Textile',
        'description' => 'Spodnie motocyklowe Dainese Super Speed Textile to spodnie z materiału tekstylnego. Posiadają wbudowane ochraniacze, system wentylacji i są kompatybilne z kurtkami Dainese.',
        'short_description' => 'Spodnie motocyklowe Dainese Super Speed Textile - lekkość i ochrona',
        'price' => 899.99,
        'sale_price' => null,
        'stock' => 30,
        'sku' => 'DAI-SST-001',
        'featured' => 0,
        'status' => 'published',
        'category_id' => 2, // Odzież
        'brand_id' => 2 // Dainese
    ],
    [
        'name' => 'Buty motocyklowe Alpinestars SMX-6 V2',
        'description' => 'Buty motocyklowe Alpinestars SMX-6 V2 to uniwersalne buty sportowe. Wykonane ze skóry z dodatkowymi wzmocnieniami, posiadają podeszwę antypoślizgową i system zapięcia.',
        'short_description' => 'Buty motocyklowe Alpinestars SMX-6 V2 - uniwersalna ochrona',
        'price' => 699.99,
        'sale_price' => 649.99,
        'stock' => 35,
        'sku' => 'ALP-SMX6-001',
        'featured' => 0,
        'status' => 'published',
        'category_id' => 2, // Odzież
        'brand_id' => 1 // Alpinestars
    ],
    [
        'name' => 'Olej silnikowy Castrol Power 1 Racing 4T',
        'description' => 'Olej silnikowy Castrol Power 1 Racing 4T to olej syntetyczny najwyższej jakości. Wysoka wydajność i ochrona silnika, odpowiedni dla nowoczesnych motocykli sportowych.',
        'short_description' => 'Olej silnikowy Castrol Power 1 Racing 4T - maksymalna wydajność',
        'price' => 119.99,
        'sale_price' => null,
        'stock' => 100,
        'sku' => 'CAS-P1R-001',
        'featured' => 0,
        'status' => 'published',
        'category_id' => 4, // Oleje i chemia
        'brand_id' => 14 // Castrol
    ],
    [
        'name' => 'Łańcuch napędowy DID 520VX3',
        'description' => 'Łańcuch napędowy DID 520VX3 to łańcuch z powłoką X-Ring. Wysoka wytrzymałość i trwałość, odpowiedni dla motocykli sportowych i turystycznych.',
        'short_description' => 'Łańcuch napędowy DID 520VX3 - trwałość i wydajność',
        'price' => 499.99,
        'sale_price' => 449.99,
        'stock' => 45,
        'sku' => 'DID-520VX3-001',
        'featured' => 0,
        'status' => 'published',
        'category_id' => 3, // Części
        'brand_id' => 15 // DID
    ],
    [
        'name' => 'Hamulce tarczowe Galfer Wave',
        'description' => 'Tarcze hamulcowe Galfer Wave to profesjonalne tarcze sportowe. Wysoka wydajność hamowania, odpowiednie dla motocykli sportowych.',
        'short_description' => 'Hamulce tarczowe Galfer Wave - profesjonalne hamowanie',
        'price' => 899.99,
        'sale_price' => null,
        'stock' => 20,
        'sku' => 'GAL-WAVE-001',
        'featured' => 1,
        'status' => 'published',
        'category_id' => 3, // Części
        'brand_id' => 16 // Galfer
    ],
    [
        'name' => 'Akumulator motocyklowy Shido YTX14-BS',
        'description' => 'Akumulator motocyklowy Shido YTX14-BS to akumulator 12V 12Ah. Wysoka wydajność i trwałość, odpowiedni dla większych motocykli.',
        'short_description' => 'Akumulator motocyklowy Shido YTX14-BS - niezawodność',
        'price' => 279.99,
        'sale_price' => 249.99,
        'stock' => 50,
        'sku' => 'SHI-YTX14-001',
        'featured' => 0,
        'status' => 'published',
        'category_id' => 5, // Akumulatory
        'brand_id' => 17 // Shido
    ],
    [
        'name' => 'Opony motocyklowe Michelin Power 5',
        'description' => 'Opony motocyklowe Michelin Power 5 to opony sportowe z doskonałą przyczepnością. Długa żywotność, odpowiednie dla motocykli sportowych.',
        'short_description' => 'Opony motocyklowe Michelin Power 5 - sportowa przyczepność',
        'price' => 1099.99,
        'sale_price' => 999.99,
        'stock' => 25,
        'sku' => 'MIC-P5-001',
        'featured' => 1,
        'status' => 'published',
        'category_id' => 3, // Części
        'brand_id' => 18 // Michelin
    ]
];

// Sprawdzenie czy wszystkie kategorie i marki istnieją
foreach ($products as $product) {
    if (!isset($categories[$product['category_id']])) {
        echo "BŁĄD: Kategoria o ID {$product['category_id']} nie istnieje dla produktu {$product['name']}<br>";
        continue;
    }
    if (!isset($brands[$product['brand_id']])) {
        echo "BŁĄD: Marka o ID {$product['brand_id']} nie istnieje dla produktu {$product['name']}<br>";
        continue;
    }
}

// Dodawanie lub aktualizacja produktów
foreach ($products as $product) {
    // Sprawdzenie czy kategoria i marka istnieją
    if (!isset($categories[$product['category_id']]) || !isset($brands[$product['brand_id']])) {
        echo "Pomijanie produktu {$product['name']} - brak kategorii lub marki<br>";
        continue;
    }

    // Generowanie unikalnego sluga
    $slug = generateUniqueSlug($conn, $product['name']);
    
    // Dodawanie nowego produktu
    $insert_query = "INSERT INTO products (name, slug, description, short_description, price, sale_price, stock, sku, featured, status, category_id, brand_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssddissiii", 
        $product['name'], 
        $slug, 
        $product['description'], 
        $product['short_description'],
        $product['price'], 
        $product['sale_price'], 
        $product['stock'], 
        $product['sku'], 
        $product['featured'],
        $product['status'],
        $product['category_id'], 
        $product['brand_id']
    );
    
    if (!$stmt->execute()) {
        echo "Błąd podczas przetwarzania produktu {$product['name']}: " . $stmt->error . "<br>";
    } else {
        echo "Produkt {$product['name']} został pomyślnie dodany.<br>";
    }
}

echo "Przetwarzanie produktów zakończone.";

// Zamknięcie połączenia
$conn->close();
?> 