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

$page_title = "Zarządzanie usługami serwisowymi";

// Obsługa formularza dodawania/edycji usługi
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dane usługi
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)str_replace(',', '.', $_POST['price']);
    $duration = (int)$_POST['duration'];
    $status = sanitize($_POST['status']);
    
    // Walidacja
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Nazwa usługi jest wymagana";
    }
    
    if (empty($description)) {
        $errors[] = "Opis usługi jest wymagany";
    }
    
    if ($price <= 0) {
        $errors[] = "Cena musi być większa od 0";
    }
    
    if ($duration <= 0) {
        $errors[] = "Czas trwania musi być większy od 0 minut";
    }
    
    // Jeśli nie ma błędów, zapisujemy usługę
    if (empty($errors)) {
        if ($service_id > 0) {
            // Aktualizacja istniejącej usługi
            $update_query = "UPDATE services SET 
                            name = '$name', 
                            description = '$description',
                            price = $price,
                            duration = $duration,
                            status = '$status'
                            WHERE id = $service_id";
            
            if ($conn->query($update_query) === TRUE) {
                $message = "Usługa została zaktualizowana pomyślnie";
            } else {
                $error = "Błąd podczas aktualizacji usługi: " . $conn->error;
            }
        } else {
            // Dodawanie nowej usługi
            $insert_query = "INSERT INTO services (name, description, price, duration, status) 
                            VALUES ('$name', '$description', $price, $duration, '$status')";
            
            if ($conn->query($insert_query) === TRUE) {
                $message = "Nowa usługa została dodana pomyślnie";
            } else {
                $error = "Błąd podczas dodawania usługi: " . $conn->error;
            }
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// Obsługa usuwania usługi
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    
    // Sprawdzenie, czy usługa ma przypisane rezerwacje
    $check_query = "SELECT COUNT(*) as count FROM service_bookings WHERE service_id = $service_id";
    $check_result = $conn->query($check_query);
    $booking_count = $check_result->fetch_assoc()['count'];
    
    if ($booking_count > 0) {
        $error = "Nie można usunąć usługi, która ma przypisane rezerwacje. Zmień status na nieaktywny.";
    } else {
        // Usuwanie usługi
        $delete_query = "DELETE FROM services WHERE id = $service_id";
        
        if ($conn->query($delete_query) === TRUE) {
            $message = "Usługa została usunięta pomyślnie";
        } else {
            $error = "Błąd podczas usuwania usługi: " . $conn->error;
        }
    }
}

// Pobieranie usługi do edycji
$service_to_edit = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $service_id = (int)$_GET['id'];
    $edit_query = "SELECT * FROM services WHERE id = $service_id";
    $edit_result = $conn->query($edit_query);
    
    if ($edit_result && $edit_result->num_rows > 0) {
        $service_to_edit = $edit_result->fetch_assoc();
    }
}

// Pobieranie wszystkich usług
$services_query = "SELECT * FROM services ORDER BY id DESC";
$services_result = $conn->query($services_query);
$services = [];

if ($services_result && $services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[] = $row;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zarządzanie usługami serwisowymi</h1>
        <button id="show-add-form" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
            Dodaj usługę
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
    
    <!-- Formularz dodawania/edycji usługi -->
    <div id="service-form" class="bg-white rounded-lg shadow-sm p-6 mb-6 <?php echo $service_to_edit || (isset($_POST['name']) && !empty($error)) ? '' : 'hidden'; ?>">
        <h2 class="text-lg font-semibold mb-4"><?php echo $service_to_edit ? 'Edytuj usługę' : 'Dodaj nową usługę'; ?></h2>
        
        <form action="" method="post" class="space-y-4">
            <?php if ($service_to_edit): ?>
            <input type="hidden" name="service_id" value="<?php echo $service_to_edit['id']; ?>">
            <?php endif; ?>
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nazwa usługi</label>
                <input type="text" id="name" name="name" value="<?php echo $service_to_edit ? $service_to_edit['name'] : (isset($_POST['name']) ? $_POST['name'] : ''); ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Opis</label>
                <textarea id="description" name="description" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required><?php echo $service_to_edit ? $service_to_edit['description'] : (isset($_POST['description']) ? $_POST['description'] : ''); ?></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Cena (zł)</label>
                    <input type="text" id="price" name="price" value="<?php echo $service_to_edit ? number_format($service_to_edit['price'], 2, ',', '') : (isset($_POST['price']) ? $_POST['price'] : ''); ?>" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">Czas trwania (minuty)</label>
                    <input type="number" id="duration" name="duration" value="<?php echo $service_to_edit ? $service_to_edit['duration'] : (isset($_POST['duration']) ? $_POST['duration'] : ''); ?>" min="1" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" required>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="active" <?php echo $service_to_edit && $service_to_edit['status'] == 'active' ? 'selected' : ''; ?>>Aktywna</option>
                        <option value="inactive" <?php echo $service_to_edit && $service_to_edit['status'] == 'inactive' ? 'selected' : ''; ?>>Nieaktywna</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" id="cancel-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Anuluj
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <?php echo $service_to_edit ? 'Zapisz zmiany' : 'Dodaj usługę'; ?>
                </button>
            </div>
        </form>
    </div>
    
    <!-- Lista usług -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($services)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Nie dodano jeszcze żadnych usług.</p>
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
                            Nazwa
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Opis
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cena
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Czas trwania
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
                    <?php foreach ($services as $service): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $service['id']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $service['name']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo mb_substr($service['description'], 0, 50); ?><?php echo strlen($service['description']) > 50 ? '...' : ''; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo number_format($service['price'], 2, ',', ' '); ?> zł</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php 
                                $hours = floor($service['duration'] / 60);
                                $minutes = $service['duration'] % 60;
                                
                                if ($hours > 0) {
                                    echo $hours . ' godz. ';
                                }
                                
                                if ($minutes > 0 || $hours == 0) {
                                    echo $minutes . ' min.';
                                }
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($service['status'] == 'active'): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                Aktywna
                            </span>
                            <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Nieaktywna
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="?edit=1&id=<?php echo $service['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                Edytuj
                            </a>
                            <a href="#" onclick="confirmDelete(<?php echo $service['id']; ?>, '<?php echo $service['name']; ?>')" class="text-red-600 hover:text-red-900">
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
// Potwierdzenie usunięcia usługi
function confirmDelete(id, name) {
    if (confirm(`Czy na pewno chcesz usunąć usługę: ${name}?`)) {
        window.location.href = `?delete=1&id=${id}`;
    }
}

// Obsługa formularza
document.addEventListener('DOMContentLoaded', function() {
    const showAddFormBtn = document.getElementById('show-add-form');
    const serviceForm = document.getElementById('service-form');
    const cancelBtn = document.getElementById('cancel-btn');
    
    if (showAddFormBtn && serviceForm) {
        showAddFormBtn.addEventListener('click', function() {
            serviceForm.classList.remove('hidden');
        });
    }
    
    if (cancelBtn && serviceForm) {
        cancelBtn.addEventListener('click', function() {
            serviceForm.classList.add('hidden');
            
            // Reset form if editing
            if (window.location.href.includes('edit=1')) {
                window.location.href = 'services.php';
            }
        });
    }
    
    // Formatowanie ceny
    const priceInput = document.getElementById('price');
    if (priceInput) {
        priceInput.addEventListener('blur', function() {
            let value = this.value.replace(/\s/g, '').replace(',', '.');
            if (!isNaN(parseFloat(value))) {
                this.value = parseFloat(value).toFixed(2).replace('.', ',');
            }
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>
