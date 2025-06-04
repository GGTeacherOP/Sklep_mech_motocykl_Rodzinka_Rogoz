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

$page_title = "Zarządzanie mechanikami";

// Obsługa formularza dodawania/edycji mechanika
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dane mechanika
    $mechanic_id = isset($_POST['mechanic_id']) ? (int)$_POST['mechanic_id'] : 0;
    $name = sanitize($_POST['name']);
    $specialization = sanitize($_POST['specialization']);
    $experience = (int)$_POST['experience'];
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);
    
    // Walidacja
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Imię i nazwisko mechanika jest wymagane";
    }
    
    if (empty($specialization)) {
        $errors[] = "Specjalizacja jest wymagana";
    }
    
    if ($experience <= 0) {
        $errors[] = "Doświadczenie musi być liczbą większą od 0";
    }
    
    if (empty($description)) {
        $errors[] = "Opis jest wymagany";
    }
    
    // Jeśli nie ma błędów, zapisujemy mechanika
    if (empty($errors)) {
        if ($mechanic_id > 0) {
            // Aktualizacja istniejącego mechanika
            $update_query = "UPDATE mechanics SET 
                            name = '$name', 
                            specialization = '$specialization',
                            experience = $experience,
                            description = '$description',
                            status = '$status'
                            WHERE id = $mechanic_id";
            
            if ($conn->query($update_query) === TRUE) {
                $message = "Mechanik został zaktualizowany pomyślnie";
                
                // Aktualizacja zdjęcia, jeśli przesłano nowe
                if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                    handleMechanicImage($mechanic_id);
                }
            } else {
                $error = "Błąd podczas aktualizacji mechanika: " . $conn->error;
            }
        } else {
            // Dodawanie nowego mechanika
            $rating = 0.0; // Domyślna wartość dla nowego mechanika
            $insert_query = "INSERT INTO mechanics (name, specialization, experience, description, rating, status) 
                            VALUES ('$name', '$specialization', $experience, '$description', $rating, '$status')";
            
            if ($conn->query($insert_query) === TRUE) {
                $mechanic_id = $conn->insert_id;
                $message = "Nowy mechanik został dodany pomyślnie";
                
                // Obsługa przesłanego zdjęcia
                if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                    handleMechanicImage($mechanic_id);
                }
            } else {
                $error = "Błąd podczas dodawania mechanika: " . $conn->error;
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Funkcja do obsługi przesyłania zdjęcia
function handleMechanicImage($mechanic_id) {
    global $conn, $error;
    
    $target_dir = dirname(__DIR__) . "/uploads/mechanics/";
    
    // Upewniamy się, że katalog istnieje
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $target_file = $target_dir . "mechanic_" . $mechanic_id . "." . $file_extension;
    $uploadOk = 1;
    $imageFileType = strtolower($file_extension);
    
    // Sprawdź czy plik jest faktycznym obrazem
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check === false) {
        $error = "Plik nie jest obrazem.";
        $uploadOk = 0;
    }
    
    // Sprawdź rozmiar pliku
    if ($_FILES["image"]["size"] > 5000000) { // 5MB
        $error = "Plik jest zbyt duży.";
        $uploadOk = 0;
    }
    
    // Zezwól tylko na określone formaty plików
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $error = "Tylko pliki JPG, JPEG, PNG i GIF są dozwolone.";
        $uploadOk = 0;
    }
    
    // Jeśli wszystko jest w porządku, spróbuj przesłać plik
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Aktualizacja ścieżki do zdjęcia w bazie danych
            $image_path = "uploads/mechanics/mechanic_" . $mechanic_id . "." . $imageFileType;
            $update_query = "UPDATE mechanics SET image_path = '$image_path' WHERE id = $mechanic_id";
            $conn->query($update_query);
        } else {
            $error = "Wystąpił błąd podczas przesyłania pliku.";
        }
    }
}

// Obsługa usuwania mechanika
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $mechanic_id = (int)$_GET['id'];
    
    // Sprawdzenie, czy mechanik ma przypisane rezerwacje
    $check_query = "SELECT COUNT(*) as count FROM service_bookings WHERE mechanic_id = $mechanic_id";
    $check_result = $conn->query($check_query);
    $booking_count = $check_result->fetch_assoc()['count'];
    
    if ($booking_count > 0) {
        $error = "Nie można usunąć mechanika, który ma przypisane rezerwacje. Zmień status na nieaktywny.";
    } else {
        // Usuwanie mechanika
        $delete_query = "DELETE FROM mechanics WHERE id = $mechanic_id";
        
        if ($conn->query($delete_query) === TRUE) {
            $message = "Mechanik został usunięty pomyślnie";
        } else {
            $error = "Błąd podczas usuwania mechanika: " . $conn->error;
        }
    }
}

// Pobieranie mechanika do edycji
$mechanic_to_edit = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $mechanic_id = (int)$_GET['id'];
    $edit_query = "SELECT * FROM mechanics WHERE id = $mechanic_id";
    $edit_result = $conn->query($edit_query);
    
    if ($edit_result && $edit_result->num_rows > 0) {
        $mechanic_to_edit = $edit_result->fetch_assoc();
    }
}

// Pobieranie wszystkich mechaników
$mechanics_query = "SELECT * FROM mechanics ORDER BY id DESC";
$mechanics_result = $conn->query($mechanics_query);
$mechanics = [];

if ($mechanics_result && $mechanics_result->num_rows > 0) {
    while ($row = $mechanics_result->fetch_assoc()) {
        $mechanics[] = $row;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zarządzanie mechanikami</h1>
        <button id="show-add-form" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
            Dodaj mechanika
        </button>
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
    
    <!-- Formularz dodawania/edycji mechanika -->
    <div id="mechanic-form" class="bg-white rounded-lg shadow-sm p-6 mb-6 <?php echo $mechanic_to_edit || (isset($_POST['name']) && !empty($error)) ? '' : 'hidden'; ?>">
        <h2 class="text-lg font-semibold mb-4"><?php echo $mechanic_to_edit ? 'Edytuj mechanika' : 'Dodaj nowego mechanika'; ?></h2>
        
        <form action="" method="post" enctype="multipart/form-data" class="space-y-4">
            <?php if ($mechanic_to_edit): ?>
            <input type="hidden" name="mechanic_id" value="<?php echo $mechanic_to_edit['id']; ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko</label>
                    <input type="text" id="name" name="name" value="<?php echo $mechanic_to_edit ? $mechanic_to_edit['name'] : (isset($_POST['name']) ? $_POST['name'] : ''); ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="specialization" class="block text-sm font-medium text-gray-700 mb-1">Specjalizacja</label>
                    <input type="text" id="specialization" name="specialization" value="<?php echo $mechanic_to_edit ? $mechanic_to_edit['specialization'] : (isset($_POST['specialization']) ? $_POST['specialization'] : ''); ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="experience" class="block text-sm font-medium text-gray-700 mb-1">Doświadczenie (lata)</label>
                    <input type="number" id="experience" name="experience" value="<?php echo $mechanic_to_edit ? $mechanic_to_edit['experience'] : (isset($_POST['experience']) ? $_POST['experience'] : ''); ?>" min="1" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="active" <?php echo $mechanic_to_edit && $mechanic_to_edit['status'] == 'active' ? 'selected' : ''; ?>>Aktywny</option>
                        <option value="inactive" <?php echo $mechanic_to_edit && $mechanic_to_edit['status'] == 'inactive' ? 'selected' : ''; ?>>Nieaktywny</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
                <textarea id="description" name="description" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required><?php echo $mechanic_to_edit ? $mechanic_to_edit['description'] : (isset($_POST['description']) ? $_POST['description'] : ''); ?></textarea>
            </div>
            
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Zdjęcie mechanika</label>
                <input type="file" id="image" name="image" class="w-full border border-gray-300 rounded-lg p-2" accept="image/*">
                <?php if ($mechanic_to_edit && !empty($mechanic_to_edit['image_path'])): ?>
                <div class="mt-2">
                    <p class="text-sm text-gray-600">Aktualne zdjęcie:</p>
                    <img src="<?php echo '../' . $mechanic_to_edit['image_path']; ?>" alt="Aktualne zdjęcie" class="w-24 h-24 object-cover rounded mt-1">
                    <p class="text-xs text-gray-500 mt-1">Prześlij nowe zdjęcie, aby zastąpić obecne</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancel-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Anuluj
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <?php echo $mechanic_to_edit ? 'Zapisz zmiany' : 'Dodaj mechanika'; ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Lista mechaników -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($mechanics)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nie dodano jeszcze żadnych mechaników.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zdjęcie
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Imię i nazwisko
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Specjalizacja
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Doświadczenie
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ocena
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Akcje
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($mechanics as $mechanic): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $mechanic['id']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if (!empty($mechanic['image_path'])): ?>
                            <img src="<?php echo '../' . $mechanic['image_path']; ?>" alt="<?php echo $mechanic['name']; ?>" class="h-10 w-10 rounded-full object-cover">
                            <?php else: ?>
                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                <i class="ri-user-line text-gray-500"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $mechanic['name']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo $mechanic['specialization']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $mechanic['experience']; ?> lat</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-900 mr-1"><?php echo number_format($mechanic['rating'], 1); ?></span>
                                <i class="ri-star-fill text-yellow-400 text-sm"></i>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($mechanic['status'] == 'active'): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Aktywny
                            </span>
                            <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Nieaktywny
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="?edit=1&id=<?php echo $mechanic['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                Edytuj
                            </a>
                            <a href="#" onclick="confirmDelete(<?php echo $mechanic['id']; ?>, '<?php echo $mechanic['name']; ?>')" class="text-red-600 hover:text-red-900">
                                Usuń
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Potwierdzenie usunięcia mechanika
function confirmDelete(id, name) {
    if (confirm(`Czy na pewno chcesz usunąć mechanika: ${name}?`)) {
        window.location.href = `?delete=1&id=${id}`;
    }
}

// Obsługa formularza
document.addEventListener('DOMContentLoaded', function() {
    const showAddFormBtn = document.getElementById('show-add-form');
    const mechanicForm = document.getElementById('mechanic-form');
    const cancelBtn = document.getElementById('cancel-btn');
    
    if (showAddFormBtn && mechanicForm) {
        showAddFormBtn.addEventListener('click', function() {
            mechanicForm.classList.remove('hidden');
        });
    }
    
    if (cancelBtn && mechanicForm) {
        cancelBtn.addEventListener('click', function() {
            mechanicForm.classList.add('hidden');
            
            // Reset form if editing
            if (window.location.href.includes('edit=1')) {
                window.location.href = 'mechanics.php';
            }
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>
