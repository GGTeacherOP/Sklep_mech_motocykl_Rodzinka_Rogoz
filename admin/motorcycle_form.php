<?php
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

$page_title = "Dodaj/Edytuj motocykl";

// Inicjalizacja zmiennych
$motorcycle = [
    'id' => 0,
    'title' => '',
    'brand' => '',
    'model' => '',
    'year' => date('Y'),
    'engine_capacity' => '',
    'mileage' => '',
    'power' => '',
    'color' => '',
    'condition' => 'good',
    'price' => '',
    'description' => '',
    'features' => '',
    'registration_number' => '',
    'vin' => '',
    'status' => 'available'
];

// Pobieranie danych motocykla do edycji
if (isset($_GET['id'])) {
    $motorcycle_id = (int)$_GET['id'];
    $query = "SELECT * FROM used_motorcycles WHERE id = $motorcycle_id";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $motorcycle = $result->fetch_assoc();
    } else {
        // Jeśli motocykl nie istnieje, przekieruj do listy motocykli
        header("Location: used_motorcycles.php");
        exit;
    }
}

// Pobieranie zdjęć motocykla (jeśli edytujemy)
$images = [];
if ($motorcycle['id'] > 0) {
    $images_query = "SELECT * FROM motorcycle_images WHERE motorcycle_id = " . $motorcycle['id'] . " ORDER BY is_main DESC, id ASC";
    $images_result = $conn->query($images_query);
    
    if ($images_result && $images_result->num_rows > 0) {
        while ($image = $images_result->fetch_assoc()) {
            $images[] = $image;
        }
    }
}

// Obsługa formularza
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dane motocykla
    $motorcycle_id = isset($_POST['motorcycle_id']) ? (int)$_POST['motorcycle_id'] : 0;
    $title = sanitize($_POST['title']);
    $brand = sanitize($_POST['brand']);
    $model = sanitize($_POST['model']);
    $year = (int)$_POST['year'];
    $engine_capacity = (int)$_POST['engine_capacity'];
    $mileage = (int)$_POST['mileage'];
    $power = (int)$_POST['power'];
    $color = sanitize($_POST['color']);
    $condition = sanitize($_POST['condition']);
    $price = (float)str_replace(',', '.', str_replace(' ', '', $_POST['price']));
    $description = sanitize($_POST['description']);
    $features = sanitize($_POST['features']);
    $registration_number = sanitize($_POST['registration_number']);
    $vin = sanitize($_POST['vin']);
    $status = sanitize($_POST['status']);
    
    // Walidacja
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Tytuł jest wymagany";
    }
    
    if (empty($brand)) {
        $errors[] = "Marka jest wymagana";
    }
    
    if (empty($model)) {
        $errors[] = "Model jest wymagany";
    }
    
    if ($year < 1900 || $year > date('Y') + 1) {
        $errors[] = "Rok produkcji musi być między 1900 a " . (date('Y') + 1);
    }
    
    if ($engine_capacity <= 0) {
        $errors[] = "Pojemność silnika musi być większa od 0";
    }
    
    if ($mileage < 0) {
        $errors[] = "Przebieg nie może być ujemny";
    }
    
    if ($price <= 0) {
        $errors[] = "Cena musi być większa od 0";
    }
    
    // Jeśli nie ma błędów, zapisujemy motocykl
    if (empty($errors)) {
        if ($motorcycle_id > 0) {
            // Aktualizacja istniejącego motocykla
            $update_query = "UPDATE used_motorcycles SET 
                            title = '$title', 
                            brand = '$brand',
                            model = '$model',
                            year = $year,
                            engine_capacity = $engine_capacity,
                            mileage = $mileage,
                            power = $power,
                            color = '$color',
                            `condition` = '$condition',
                            price = $price,
                            description = '$description',
                            features = '$features',
                            registration_number = '$registration_number',
                            vin = '$vin',
                            status = '$status',
                            updated_at = NOW()
                            WHERE id = $motorcycle_id";
            
            if ($conn->query($update_query) === TRUE) {
                $message = "Motocykl został zaktualizowany pomyślnie";
                
                // Obsługa przesłanych zdjęć
                handleMotorcycleImages($motorcycle_id, isset($_POST['main_image']) ? $_POST['main_image'] : 0);
            } else {
                $error = "Błąd podczas aktualizacji motocykla: " . $conn->error;
            }
        } else {
            // Dodawanie nowego motocykla
            $insert_query = "INSERT INTO used_motorcycles (
                            title, brand, model, year, engine_capacity, mileage, power, color, 
                            `condition`, price, description, features, registration_number, vin, status, created_at, updated_at
                            ) VALUES (
                            '$title', '$brand', '$model', $year, $engine_capacity, $mileage, $power, '$color', 
                            '$condition', $price, '$description', '$features', '$registration_number', '$vin', '$status', 
                            NOW(), NOW()
                            )";
            
            if ($conn->query($insert_query) === TRUE) {
                $motorcycle_id = $conn->insert_id;
                $message = "Nowy motocykl został dodany pomyślnie";
                
                // Obsługa przesłanych zdjęć
                handleMotorcycleImages($motorcycle_id, isset($_POST['main_image']) ? $_POST['main_image'] : 0);
            } else {
                $error = "Błąd podczas dodawania motocykla: " . $conn->error;
            }
        }
        
        // Po zakończeniu operacji, przekieruj do listy motocykli
        if (empty($error)) {
            header("Location: used_motorcycles.php?message=" . urlencode($message));
            exit;
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Funkcja do obsługi przesyłania zdjęć
function handleMotorcycleImages($motorcycle_id, $main_image_id) {
    global $conn, $error;
    
    $target_dir = dirname(__DIR__) . "/uploads/motorcycles/";
    
    // Upewniamy się, że katalog istnieje
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Obsługa przesłanych nowych zdjęć
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $image_count = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $image_count; $i++) {
            if ($_FILES['images']['error'][$i] == 0) {
                $temp_name = $_FILES['images']['tmp_name'][$i];
                $file_name = $_FILES['images']['name'][$i];
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = "motorcycle_" . $motorcycle_id . "_" . time() . "_" . $i . "." . $file_extension;
                $target_file = $target_dir . $new_file_name;
                
                // Sprawdź czy plik jest faktycznym obrazem
                $check = getimagesize($temp_name);
                if($check !== false) {
                    // Przenieś plik do docelowego katalogu
                    if (move_uploaded_file($temp_name, $target_file)) {
                        // Zapisz informację o zdjęciu w bazie danych
                        $image_path = "uploads/motorcycles/" . $new_file_name;
                        $is_main = 0;
                        
                        // Jeśli to pierwsze zdjęcie i nie wybrano głównego, ustaw jako główne
                        if ($i == 0 && $main_image_id == 0) {
                            $is_main = 1;
                        }
                        
                        $insert_image_query = "INSERT INTO motorcycle_images (motorcycle_id, image_path, is_main) VALUES ($motorcycle_id, '$image_path', $is_main)";
                        $conn->query($insert_image_query);
                    }
                }
            }
        }
    }
    
    // Obsługa zmiany głównego zdjęcia
    if ($main_image_id > 0) {
        // Najpierw resetuj wszystkie zdjęcia jako nie główne
        $reset_main_query = "UPDATE motorcycle_images SET is_main = 0 WHERE motorcycle_id = $motorcycle_id";
        $conn->query($reset_main_query);
        
        // Ustaw wybrane zdjęcie jako główne
        $set_main_query = "UPDATE motorcycle_images SET is_main = 1 WHERE id = $main_image_id AND motorcycle_id = $motorcycle_id";
        $conn->query($set_main_query);
    }
    
    // Obsługa usuwania zdjęć
    if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
        $delete_ids = array_map('intval', $_POST['delete_images']);
        $delete_ids_str = implode(',', $delete_ids);
        
        // Pobierz ścieżki do zdjęć przed usunięciem
        $paths_query = "SELECT image_path FROM motorcycle_images WHERE id IN ($delete_ids_str) AND motorcycle_id = $motorcycle_id";
        $paths_result = $conn->query($paths_query);
        $paths = [];
        
        if ($paths_result && $paths_result->num_rows > 0) {
            while ($row = $paths_result->fetch_assoc()) {
                $paths[] = dirname(__DIR__) . '/' . $row['image_path'];
            }
        }
        
        // Usuń rekordy z bazy danych
        $delete_query = "DELETE FROM motorcycle_images WHERE id IN ($delete_ids_str) AND motorcycle_id = $motorcycle_id";
        $conn->query($delete_query);
        
        // Usuń pliki z dysku
        foreach ($paths as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <?php echo $motorcycle['id'] > 0 ? 'Edytuj motocykl' : 'Dodaj nowy motocykl'; ?>
        </h1>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="bg-green-50 text-green-800 rounded-lg p-4 mb-6">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-50 text-red-800 rounded-lg p-4 mb-6">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <!-- Formularz motocykla -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="" method="post" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="motorcycle_id" value="<?php echo $motorcycle['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h2 class="text-lg font-semibold mb-4">Podstawowe informacje</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Tytuł oferty *</label>
                            <input type="text" id="title" name="title" value="<?php echo $motorcycle['title']; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Marka *</label>
                                <input type="text" id="brand" name="brand" value="<?php echo $motorcycle['brand']; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Model *</label>
                                <input type="text" id="model" name="model" value="<?php echo $motorcycle['model']; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Rok produkcji *</label>
                                <input type="number" id="year" name="year" value="<?php echo $motorcycle['year']; ?>" min="1900" max="<?php echo date('Y') + 1; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="engine_capacity" class="block text-sm font-medium text-gray-700 mb-1">Pojemność silnika (cm³) *</label>
                                <input type="number" id="engine_capacity" name="engine_capacity" value="<?php echo $motorcycle['engine_capacity']; ?>" min="1" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="power" class="block text-sm font-medium text-gray-700 mb-1">Moc (KM)</label>
                                <input type="number" id="power" name="power" value="<?php echo $motorcycle['power']; ?>" min="0" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="mileage" class="block text-sm font-medium text-gray-700 mb-1">Przebieg (km) *</label>
                                <input type="number" id="mileage" name="mileage" value="<?php echo $motorcycle['mileage']; ?>" min="0" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="color" class="block text-sm font-medium text-gray-700 mb-1">Kolor</label>
                                <input type="text" id="color" name="color" value="<?php echo $motorcycle['color']; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">Stan *</label>
                                <select id="condition" name="condition" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="excellent" <?php echo $motorcycle['condition'] == 'excellent' ? 'selected' : ''; ?>>Doskonały</option>
                                    <option value="very_good" <?php echo $motorcycle['condition'] == 'very_good' ? 'selected' : ''; ?>>Bardzo dobry</option>
                                    <option value="good" <?php echo $motorcycle['condition'] == 'good' ? 'selected' : ''; ?>>Dobry</option>
                                    <option value="average" <?php echo $motorcycle['condition'] == 'average' ? 'selected' : ''; ?>>Średni</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Cena (zł) *</label>
                            <input type="text" id="price" name="price" value="<?php echo $motorcycle['price']; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select id="status" name="status" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                                <option value="available" <?php echo $motorcycle['status'] == 'available' ? 'selected' : ''; ?>>Dostępny</option>
                                <option value="reserved" <?php echo $motorcycle['status'] == 'reserved' ? 'selected' : ''; ?>>Zarezerwowany</option>
                                <option value="sold" <?php echo $motorcycle['status'] == 'sold' ? 'selected' : ''; ?>>Sprzedany</option>
                                <option value="hidden" <?php echo $motorcycle['status'] == 'hidden' ? 'selected' : ''; ?>>Ukryty</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-lg font-semibold mb-4">Dodatkowe informacje</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Opis *</label>
                            <textarea id="description" name="description" rows="6" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required><?php echo $motorcycle['description']; ?></textarea>
                        </div>
                        
                        <div>
                            <label for="features" class="block text-sm font-medium text-gray-700 mb-1">Wyposażenie i cechy</label>
                            <textarea id="features" name="features" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?php echo $motorcycle['features']; ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Wpisz elementy wyposażenia oddzielone przecinkami lub w nowych liniach.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-1">Numer rejestracyjny</label>
                                <input type="text" id="registration_number" name="registration_number" value="<?php echo $motorcycle['registration_number']; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label for="vin" class="block text-sm font-medium text-gray-700 mb-1">Numer VIN</label>
                                <input type="text" id="vin" name="vin" value="<?php echo $motorcycle['vin']; ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sekcja zarządzania zdjęciami -->
            <div class="mt-8 border-t pt-6">
                <h2 class="text-lg font-semibold mb-4">Zdjęcia motocykla</h2>
                
                <?php if (!empty($images)): ?>
                <div class="mb-6">
                    <p class="text-sm text-gray-700 mb-3">Aktualne zdjęcia:</p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php foreach ($images as $image): ?>
                        <div class="relative group">
                            <div class="aspect-w-3 aspect-h-2 overflow-hidden rounded-lg">
                                <img src="<?php echo '../' . $image['image_path']; ?>" alt="Zdjęcie motocykla" class="object-cover h-full w-full">
                            </div>
                            <div class="absolute inset-0 flex flex-col items-center justify-center opacity-0 bg-black bg-opacity-50 group-hover:opacity-100 transition-opacity rounded-lg">
                                <div class="flex space-x-2 p-2">
                                    <label class="text-white text-xs flex items-center cursor-pointer">
                                        <input type="radio" name="main_image" value="<?php echo $image['id']; ?>" <?php echo $image['is_main'] ? 'checked' : ''; ?> class="mr-1">
                                        Główne
                                    </label>
                                    <label class="text-white text-xs flex items-center cursor-pointer">
                                        <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>" class="mr-1">
                                        Usuń
                                    </label>
                                </div>
                            </div>
                            <?php if ($image['is_main']): ?>
                            <div class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">
                                Główne
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div>
                    <label for="images" class="block text-sm font-medium text-gray-700 mb-1">Dodaj nowe zdjęcia:</label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*" class="w-full border border-gray-300 rounded-lg p-2">
                    <p class="text-xs text-gray-500 mt-1">Możesz wybrać wiele zdjęć naraz. Maksymalny rozmiar pojedynczego pliku: 5MB. Obsługiwane formaty: JPG, JPEG, PNG.</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="used_motorcycles.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Anuluj
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <?php echo $motorcycle['id'] > 0 ? 'Zapisz zmiany' : 'Dodaj motocykl'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Formatowanie pola ceny
    const priceInput = document.getElementById('price');
    if (priceInput) {
        // Formatuj wartość początkową
        if (priceInput.value) {
            priceInput.value = Number(priceInput.value).toLocaleString('pl-PL');
        }
        
        // Formatuj przy utracie fokusu
        priceInput.addEventListener('blur', function() {
            let value = this.value.replace(/\s/g, '').replace(',', '.');
            if (!isNaN(parseFloat(value))) {
                this.value = Number(parseFloat(value)).toLocaleString('pl-PL');
            }
        });
    }
    
    // Podgląd wybranych zdjęć przed przesłaniem
    const imagesInput = document.getElementById('images');
    if (imagesInput) {
        imagesInput.addEventListener('change', function() {
            const previewContainer = document.createElement('div');
            previewContainer.className = 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mt-4';
            previewContainer.id = 'image-preview';
            
            // Usuń istniejący podgląd
            const existingPreview = document.getElementById('image-preview');
            if (existingPreview) {
                existingPreview.remove();
            }
            
            if (this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const preview = document.createElement('div');
                            preview.className = 'aspect-w-3 aspect-h-2 overflow-hidden rounded-lg';
                            
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'object-cover h-full w-full';
                            img.alt = 'Podgląd zdjęcia';
                            
                            preview.appendChild(img);
                            previewContainer.appendChild(preview);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                }
                
                imagesInput.parentNode.insertBefore(previewContainer, imagesInput.nextSibling);
            }
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>
