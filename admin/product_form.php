<?php
// Włączenie pełnego raportowania błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Stała określająca, że jesteśmy w panelu administracyjnym
define('ADMIN_PANEL', true);

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

$page_title = "Zarządzanie produktem";

// Inicjalizacja zmiennych
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_editing = $product_id > 0;
$product = [
    'id' => 0,
    'name' => '',
    'slug' => '',
    'description' => '',
    'price' => 0,
    'sale_price' => null,
    'stock' => 0,
    'category_id' => 0,
    'brand_id' => 0,
    'featured' => 0,
    'status' => 'published'
];

$images = [];
$error = '';
$success = '';

// Pobieranie danych produktu, jeśli edytujemy istniejący produkt
if ($is_editing) {
    $product_query = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        $error = "Nie znaleziono produktu o podanym ID.";
    }
    
    // Pobieranie zdjęć produktu
    $images_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
    $stmt = $conn->prepare($images_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $images_result = $stmt->get_result();
    
    if ($images_result && $images_result->num_rows > 0) {
        while ($image = $images_result->fetch_assoc()) {
            $images[] = $image;
        }
    }
}

// Pobieranie kategorii
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($category = $categories_result->fetch_assoc()) {
        $categories[] = $category;
    }
}

// Pobieranie marek
$brands_query = "SELECT * FROM brands ORDER BY name ASC";
$brands_result = $conn->query($brands_query);
$brands = [];

if ($brands_result && $brands_result->num_rows > 0) {
    while ($brand = $brands_result->fetch_assoc()) {
        $brands[] = $brand;
    }
}

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobieranie i walidacja danych
    $name = $_POST['name'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $description = $_POST['description'] ?? ''; // Treść HTML nie powinna być sanityzowana całkowicie
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    $brand_id = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = $_POST['status'] ?? 'published';
    
    // Walidacja
    if (empty($name) || empty($slug) || empty($description) || $price <= 0) {
        $error = "Wypełnij wszystkie wymagane pola.";
    } else {
        if ($is_editing) {
            // Aktualizacja produktu
            $update_query = "UPDATE products SET 
                            name = ?,
                            slug = ?,
                            description = ?,
                            price = ?,
                            sale_price = ?,
                            stock = ?,
                            category_id = ?,
                            brand_id = ?,
                            featured = ?,
                            status = ?,
                            updated_at = NOW()
                            WHERE id = ?";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sssddiiiisi", 
                $name, $slug, $description, $price, $sale_price, 
                $stock, $category_id, $brand_id, $featured, $status, $product_id
            );
            
            if ($stmt->execute()) {
                $success = "Produkt został zaktualizowany.";
                
                // Aktualizacja danych w zmiennej $product
                $product['name'] = $name;
                $product['slug'] = $slug;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['sale_price'] = $sale_price;
                $product['stock'] = $stock;
                $product['category_id'] = $category_id;
                $product['brand_id'] = $brand_id;
                $product['featured'] = $featured;
                $product['status'] = $status;
            } else {
                $error = "Wystąpił błąd podczas aktualizacji produktu: " . $conn->error;
            }
        } else {
            // Dodawanie nowego produktu
            $insert_query = "INSERT INTO products (name, slug, description, price, sale_price, stock, 
                            category_id, brand_id, featured, status, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($insert_query);
            
            if (!$stmt) {
                $error = "Błąd przygotowania zapytania: " . $conn->error;
            } else {
                $stmt->bind_param("sssddiiiis", 
                    $name, $slug, $description, $price, $sale_price, 
                    $stock, $category_id, $brand_id, $featured, $status
                );
                
                if ($stmt->execute()) {
                    $product_id = $conn->insert_id;
                    $is_editing = true;
                    $success = "Produkt został dodany.";
                    
                    // Aktualizacja danych w zmiennej $product
                    $product = [
                        'id' => $product_id,
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'price' => $price,
                        'sale_price' => $sale_price,
                        'stock' => $stock,
                        'category_id' => $category_id,
                        'brand_id' => $brand_id,
                        'featured' => $featured,
                        'status' => $status
                    ];
                } else {
                    $error = "Wystąpił błąd podczas dodawania produktu: " . $conn->error;
                }
            }
        }
        
        // Obsługa przesyłania zdjęć
        if ($product_id > 0 && isset($_FILES['images']) && $_FILES['images']['error'][0] !== 4) {
            $uploaded_files = rearrangeFilesArray($_FILES['images']);
            $upload_dir = $base_path . '/uploads/products/';
            
            // Diagnostyka uprawnień dla sekcji przesyłania zdjęć
            $upload_diagnostics = "";
            
            // Tworzenie katalogu, jeśli nie istnieje
            $dir_exists = is_dir($upload_dir);
            $upload_diagnostics .= "Katalog $upload_dir " . ($dir_exists ? "istnieje" : "nie istnieje") . ". ";
            
            if (!$dir_exists) {
                // Tworzenie katalogu z pełnymi uprawnieniami
                $dir_exists = mkdir($upload_dir, 0777, true);
                $upload_diagnostics .= "Próba utworzenia katalogu: " . ($dir_exists ? "udana" : "nieudana") . ". ";
                
                if ($dir_exists) {
                    // Nadanie pełnych uprawnień
                    chmod($upload_dir, 0777);
                } else {
                    $error = "Nie można utworzyć katalogu dla zdjęć: $upload_dir. $upload_diagnostics";
                    error_log("Błąd tworzenia katalogu: $upload_dir");
                }
            } else {
                // Sprawdź uprawnienia
                $is_writable = is_writable($upload_dir);
                $upload_diagnostics .= "Katalog " . ($is_writable ? "ma" : "nie ma") . " uprawnień do zapisu. ";
                
                if (!$is_writable) {
                    // Spróbuj ustawić uprawnienia
                    chmod($upload_dir, 0777);
                    $is_writable = is_writable($upload_dir);
                    $upload_diagnostics .= "Po próbie aktualizacji katalog " . ($is_writable ? "ma" : "nadal nie ma") . " uprawnień do zapisu.";
                }
            }
            
            $success_upload_count = 0;
            $upload_errors = [];
            
            foreach ($uploaded_files as $file) {
                if ($file['error'] === 0) {
                    // Generowanie unikalnej nazwy pliku i usuwanie polskich znaków
                    $original_name = basename($file['name']);
                    $file_name = uniqid() . '_' . $original_name;
                    $target_file = $upload_dir . $file_name;
                    
                    // Sprawdzenie, czy plik jest obrazem
                    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_type, $allowed_types)) {
                        // Diagnostyka przesyłania
                        $file_diagnostics = "";
                        $file_diagnostics .= "Plik tymczasowy {$file['tmp_name']} " . (file_exists($file['tmp_name']) ? "istnieje" : "nie istnieje") . ". ";
                        $file_diagnostics .= "Katalog docelowy " . (is_writable($upload_dir) ? "jest zapisywalny" : "nie jest zapisywalny") . ". ";
                        
                        $upload_success = move_uploaded_file($file['tmp_name'], $target_file);
                        
                        if ($upload_success) {
                            // Zmiana uprawnień pliku
                            chmod($target_file, 0666);
                            $success_upload_count++;
                            
                            // Sprawdzamy ile już jest zdjęć dla tego produktu - jeśli brak, to ustawiamy jako główne
                            $count_query = "SELECT COUNT(*) AS count FROM product_images WHERE product_id = ?";
                            $stmt = $conn->prepare($count_query);
                            $stmt->bind_param("i", $product_id);
                            $stmt->execute();
                            $count_result = $stmt->get_result()->fetch_assoc();
                            $is_main = ($count_result['count'] == 0) ? 1 : 0;
                            
                            // Ścieżka względna do przechowywania w bazie danych
                            $relative_path = 'uploads/products/' . $file_name;
                            
                            // Dodawanie zdjęcia do bazy danych
                            $image_query = "INSERT INTO product_images (product_id, image_path, is_main, created_at)
                                           VALUES (?, ?, ?, NOW())";
                            $stmt = $conn->prepare($image_query);
                            if (!$stmt) {
                                $upload_errors[] = "Błąd przygotowania zapytania: " . $conn->error;
                                continue;
                            }
                            
                            $stmt->bind_param("isi", $product_id, $relative_path, $is_main);
                            $db_success = $stmt->execute();
                            
                            if ($db_success) {
                                // Dodanie do tablicy zdjęć
                                $images[] = [
                                    'id' => $conn->insert_id,
                                    'product_id' => $product_id,
                                    'image_path' => $relative_path,
                                    'is_main' => $is_main
                                ];
                            } else {
                                $upload_errors[] = "Błąd zapisu do bazy danych: " . $stmt->error;
                            }
                        } else {
                            $upload_errors[] = "Nie można przenieść pliku do katalogu docelowego: $file_diagnostics";
                        }
                    } else {
                        $upload_errors[] = "Nieprawidłowy typ pliku. Dozwolone: jpg, jpeg, png, gif.";
                    }
                } else {
                    $upload_errors[] = "Błąd przesyłania pliku: kod {$file['error']}.";
                }
            }
            
            // Dodaj komunikaty o wynikach przesyłania
            if ($success_upload_count > 0) {
                $success .= " Przesłano pomyślnie $success_upload_count zdjęć.";
            }
            
            if (!empty($upload_errors)) {
                $error .= " Błędy przesyłania zdjęć: " . implode(', ', $upload_errors);
            }
        }
    }
}

// Obsługa usuwania zdjęcia
if (isset($_GET['action']) && $_GET['action'] === 'delete_image' && isset($_GET['image_id'])) {
    $image_id = (int)$_GET['image_id'];
    
    // Pobieranie informacji o zdjęciu
    $image_query = "SELECT * FROM product_images WHERE id = ? AND product_id = ?";
    $stmt = $conn->prepare($image_query);
    $stmt->bind_param("ii", $image_id, $product_id);
    $stmt->execute();
    $image_result = $stmt->get_result();
    
    if ($image_result && $image_result->num_rows > 0) {
        $image = $image_result->fetch_assoc();
        
        // Usuwanie pliku
        $file_path = $base_path . '/' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Usuwanie z bazy danych
        $delete_query = "DELETE FROM product_images WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $image_id);
        
        if ($stmt->execute()) {
            // Jeśli to było główne zdjęcie, ustaw inne zdjęcie jako główne
            if ($image['is_main'] == 1) {
                // Znajdź inne zdjęcie do ustawienia jako główne
                $find_other_image = "SELECT id FROM product_images WHERE product_id = ? AND id != ? LIMIT 1";
                $stmt = $conn->prepare($find_other_image);
                $stmt->bind_param("ii", $product_id, $image_id);
                $stmt->execute();
                $other_image_result = $stmt->get_result();
                
                if ($other_image_result && $other_image_result->num_rows > 0) {
                    $other_image = $other_image_result->fetch_assoc();
                    $update_main_query = "UPDATE product_images SET is_main = 1 WHERE id = ?";
                    $stmt = $conn->prepare($update_main_query);
                    $stmt->bind_param("i", $other_image['id']);
                    $stmt->execute();
                }
            }
            
            // Przekierowanie z powrotem do formularza
            header("Location: product_form.php?id=$product_id&success=image_deleted");
            exit;
        }
    }
}

// Funkcja do przeorganizowania tablicy $_FILES przy wielu plikach
function rearrangeFilesArray($files_arr) {
    $file_ary = array();
    $file_count = count($files_arr['name']);
    $file_keys = array_keys($files_arr);
    
    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $file_ary[$i][$key] = $files_arr[$key][$i];
        }
    }
    
    return $file_ary;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $is_editing ? 'Edycja produktu' : 'Dodaj nowy produkt'; ?></h1>
        <p class="text-gray-600"><?php echo $is_editing ? 'Edytuj informacje o produkcie' : 'Wypełnij formularz, aby dodać nowy produkt'; ?></p>
    </div>
    
    <?php if (!empty($error)): ?>
    <div class="mb-6 p-4 rounded-lg bg-red-50 text-red-700">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="mb-6 p-4 rounded-lg bg-green-50 text-green-700">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-sm">
        <form method="POST" enctype="multipart/form-data">
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Lewa kolumna: Podstawowe informacje -->
                    <div>
                        <h2 class="text-lg font-semibold mb-4">Podstawowe informacje</h2>
                        
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nazwa produktu</label>
                            <input type="text" name="name" id="name" required
                                value="<?php echo htmlspecialchars($product['name']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                onkeyup="if (!document.getElementById('slug').value) generateSlug()">
                        </div>
                        
                        <div class="mb-4">
                            <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug (URL)</label>
                            <div class="flex">
                                <input type="text" name="slug" id="slug" required
                                    value="<?php echo htmlspecialchars($product['slug']); ?>"
                                    class="flex-grow px-4 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500">
                                <button type="button" onclick="generateSlug()" class="bg-gray-100 border border-gray-300 px-3 py-2 rounded-r-md hover:bg-gray-200">
                                    Generuj
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-4 grid grid-cols-2 gap-4">
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Cena regularna (zł)</label>
                                <input type="number" name="price" id="price" required step="0.01" min="0"
                                    value="<?php echo number_format($product['price'], 2, '.', ''); ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-1">Cena promocyjna (zł)</label>
                                <input type="number" name="sale_price" id="sale_price" step="0.01" min="0"
                                    value="<?php echo $product['sale_price'] !== null ? number_format($product['sale_price'], 2, '.', '') : ''; ?>"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="stock" class="block text-sm font-medium text-gray-700 mb-1">Stan magazynowy</label>
                            <input type="number" name="stock" id="stock" required min="0"
                                value="<?php echo (int)$product['stock']; ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="mb-4">
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Kategoria</label>
                            <select name="category_id" id="category_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Wybierz kategorię</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-1">Marka</label>
                            <select name="brand_id" id="brand_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Wybierz markę</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo $product['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <div class="flex items-center">
                                <input type="checkbox" name="featured" id="featured" value="1" <?php echo $product['featured'] ? 'checked' : ''; ?>
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="featured" class="ml-2 block text-sm text-gray-700">
                                    Wyróżniony produkt
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="published" <?php echo $product['status'] === 'published' ? 'selected' : ''; ?>>Aktywny</option>
                                <option value="out_of_stock" <?php echo $product['status'] === 'out_of_stock' ? 'selected' : ''; ?>>Brak na stanie</option>
                                <option value="draft" <?php echo $product['status'] === 'draft' ? 'selected' : ''; ?>>Szkic</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Prawa kolumna: Opis i zdjęcia -->
                    <div>
                        <h2 class="text-lg font-semibold mb-4">Opis i dodatkowe informacje</h2>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Opis produktu</label>
                            <textarea name="description" id="description" rows="8" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Zdjęcia produktu</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <i class="ri-image-add-line text-4xl text-gray-400"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="images" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                            <span>Dodaj zdjęcia</span>
                                            <input id="images" name="images[]" type="file" class="sr-only" multiple accept="image/*">
                                        </label>
                                        <p class="pl-1">lub przeciągnij i upuść</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        PNG, JPG, GIF do 10MB
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($images)): ?>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Obecne zdjęcia</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($images as $image): ?>
                                <div class="relative">
                                    <img src="/<?php echo htmlspecialchars($image['image_path']); ?>" alt="Zdjęcie produktu" class="w-full h-32 object-cover rounded-md">
                                    <div class="absolute top-2 right-2">
                                        <a href="?id=<?php echo $product_id; ?>&action=delete_image&image_id=<?php echo $image['id']; ?>"
                                           onclick="return confirm('Czy na pewno chcesz usunąć to zdjęcie?')"
                                           class="bg-red-100 rounded-full p-1 text-red-600 hover:bg-red-200">
                                            <i class="ri-delete-bin-line"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t flex justify-between items-center">
                <a href="manage_products.php" class="text-gray-600 hover:text-gray-800">
                    <i class="ri-arrow-left-line mr-1"></i> Powrót do listy
                </a>
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition">
                    <?php echo $is_editing ? 'Zapisz zmiany' : 'Dodaj produkt'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function generateSlug() {
    const name = document.getElementById('name').value;
    if (!name) return;
    
    // Konwersja na małe litery
    let slug = name.toLowerCase();
    
    // Zamiana polskich znaków
    slug = slug.replace(/ą/g, 'a').replace(/ć/g, 'c').replace(/ę/g, 'e').replace(/ł/g, 'l')
               .replace(/ń/g, 'n').replace(/ó/g, 'o').replace(/ś/g, 's').replace(/ż/g, 'z')
               .replace(/ź/g, 'z');
    
    // Usunięcie znaków specjalnych i zamiana spacji na myślniki
    slug = slug.replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-');
    
    document.getElementById('slug').value = slug;
}
</script>

<?php
include 'includes/footer.php';
?>
