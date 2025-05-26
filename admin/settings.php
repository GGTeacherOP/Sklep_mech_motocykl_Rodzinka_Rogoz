<?php
// Włączenie wyświetlania błędów PHP
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

// Sprawdzenie uprawnień - tylko admin i właściciel mają dostęp do ustawień
if ($_SESSION['admin_role'] !== 'admin' && $_SESSION['admin_role'] !== 'owner') {
    // Przekierowanie do panelu
    header("Location: index.php?error=permission");
    exit;
}

$page_title = "Ustawienia Sklepu";

// Sprawdzenie istnienia tabeli
$check_table_query = "SHOW TABLES LIKE 'shop_settings'";
$table_exists = $conn->query($check_table_query)->num_rows > 0;

if (!$table_exists) {
    // Wykonanie skryptu tworzącego tabelę
    $sql_path = $base_path . '/database/add_settings_table.sql';
    if (file_exists($sql_path)) {
        $sql = file_get_contents($sql_path);
        $conn->multi_query($sql);
        
        // Oczekiwanie na zakończenie wszystkich zapytań
        while ($conn->more_results() && $conn->next_result()) {
            // Pomiń wyniki, jeśli istnieją
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
        
        $success_msg = "Tabela ustawień została utworzona pomyślnie.";
    } else {
        $error_msg = "Nie można znaleźć pliku SQL do tworzenia tabeli ustawień.";
    }
}

// Pobranie aktywnej grupy ustawień
$active_group = isset($_GET['group']) ? sanitize($_GET['group']) : 'general';

// Obsługa zapisywania ustawień
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $settings = $_POST['settings'] ?? [];
    $errors = [];
    $success = true;
    
    foreach ($settings as $key => $value) {
        $key = sanitize($key);
        
        // Jeśli to jest pole pliku, obsługa pliku
        if (isset($_FILES['setting_files']) && isset($_FILES['setting_files']['name'][$key]) && !empty($_FILES['setting_files']['name'][$key])) {
            $file = $_FILES['setting_files'];
            $file_name = $file['name'][$key];
            $file_tmp = $file['tmp_name'][$key];
            $file_error = $file['error'][$key];
            
            if ($file_error === 0) {
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico'];
                
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_file_name = 'assets/images/settings/' . uniqid('setting_') . '.' . $file_ext;
                    $upload_path = $base_path . '/' . $new_file_name;
                    
                    // Upewnij się, że katalog istnieje
                    $upload_dir = dirname($upload_path);
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $value = $new_file_name;
                    } else {
                        $errors[] = "Nie udało się przesłać pliku: " . $file_name;
                        $success = false;
                    }
                } else {
                    $errors[] = "Niedozwolony typ pliku: " . $file_name;
                    $success = false;
                }
            } elseif ($file_error !== 4) { // 4 oznacza, że nie wybrano pliku
                $errors[] = "Błąd podczas przesyłania pliku: " . $file_name;
                $success = false;
            }
        }
        
        // Aktualizacja ustawienia w bazie danych
        $update_query = "UPDATE shop_settings SET setting_value = ? WHERE setting_key = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ss", $value, $key);
        
        if (!$stmt->execute()) {
            $errors[] = "Błąd podczas aktualizacji ustawienia: " . $key;
            $success = false;
        }
    }
    
    if ($success) {
        $success_msg = "Ustawienia zostały zapisane pomyślnie.";
    } else {
        $error_msg = implode("<br>", $errors);
    }
}

// Pobranie listy grup ustawień
$groups_query = "SELECT DISTINCT setting_group FROM shop_settings ORDER BY setting_group";
$groups_result = $conn->query($groups_query);
$setting_groups = [];

if ($groups_result) {
    while ($row = $groups_result->fetch_assoc()) {
        $setting_groups[] = $row['setting_group'];
    }
}

// Pobranie ustawień z aktywnej grupy
$settings_query = "SELECT * FROM shop_settings WHERE setting_group = ? ORDER BY id";
$stmt = $conn->prepare($settings_query);
$stmt->bind_param("s", $active_group);
$stmt->execute();
$settings_result = $stmt->get_result();
$settings = [];

if ($settings_result) {
    while ($row = $settings_result->fetch_assoc()) {
        $settings[] = $row;
    }
}

// Funkcja do formatowania nazwy grupy
function format_group_name($group) {
    $group = str_replace('_', ' ', $group);
    return ucwords($group);
}

include 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Główna zawartość -->
    <div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Ustawienia Sklepu</h1>
                <p class="text-gray-600">Zarządzaj ustawieniami i konfiguracją sklepu</p>
            </div>
        </div>
        
        <?php if (isset($error_msg)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error_msg; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($success_msg)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo $success_msg; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Menu boczne ustawień -->
            <div class="md:col-span-3">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold">Kategorie ustawień</h2>
                    </div>
                    <div class="p-4">
                        <nav class="space-y-1">
                            <?php foreach ($setting_groups as $group): ?>
                            <a href="?group=<?php echo $group; ?>" class="block py-2 px-3 rounded-md <?php echo $active_group === $group ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                <?php 
                                $icon_class = '';
                                switch ($group) {
                                    case 'general':
                                        $icon_class = 'ri-settings-3-line';
                                        break;
                                    case 'email':
                                        $icon_class = 'ri-mail-line';
                                        break;
                                    case 'seo':
                                        $icon_class = 'ri-line-chart-line';
                                        break;
                                    case 'sales':
                                        $icon_class = 'ri-shopping-cart-line';
                                        break;
                                    case 'reviews':
                                        $icon_class = 'ri-star-line';
                                        break;
                                    case 'social':
                                        $icon_class = 'ri-share-line';
                                        break;
                                    default:
                                        $icon_class = 'ri-settings-line';
                                }
                                ?>
                                <div class="flex items-center">
                                    <i class="<?php echo $icon_class; ?> mr-3 text-lg"></i>
                                    <span><?php echo format_group_name($group); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>
            </div>
            
            <!-- Formularz ustawień -->
            <div class="md:col-span-9">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold">Ustawienia: <?php echo format_group_name($active_group); ?></h2>
                    </div>
                    
                    <?php if (empty($settings)): ?>
                    <div class="p-6">
                        <p class="text-gray-500 text-center">Brak ustawień w tej kategorii.</p>
                    </div>
                    <?php else: ?>
                    <form action="" method="POST" enctype="multipart/form-data" class="p-4">
                        <input type="hidden" name="save_settings" value="1">
                        
                        <?php foreach ($settings as $setting): ?>
                        <div class="mb-6 pb-6 border-b border-gray-200 last:border-b-0 last:pb-0">
                            <label for="setting_<?php echo $setting['id']; ?>" class="block text-sm font-medium text-gray-700 mb-1">
                                <?php echo htmlspecialchars($setting['description']); ?>
                            </label>
                            
                            <?php 
                            // Renderowanie odpowiedniego pola w zależności od typu
                            switch ($setting['input_type']) {
                                case 'textarea':
                                    echo '<textarea id="setting_' . $setting['id'] . '" name="settings[' . $setting['setting_key'] . ']" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">' . htmlspecialchars($setting['setting_value']) . '</textarea>';
                                    break;
                                    
                                case 'checkbox':
                                    echo '<div class="flex items-center">';
                                    echo '<input type="hidden" name="settings[' . $setting['setting_key'] . ']" value="0">';
                                    echo '<input type="checkbox" id="setting_' . $setting['id'] . '" name="settings[' . $setting['setting_key'] . ']" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4" ' . ($setting['setting_value'] == '1' ? 'checked' : '') . '>';
                                    echo '</div>';
                                    break;
                                    
                                case 'select':
                                    $options = json_decode($setting['options'], true) ?: [];
                                    echo '<select id="setting_' . $setting['id'] . '" name="settings[' . $setting['setting_key'] . ']" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                                    
                                    // Jeśli opcje są w formacie JSON
                                    if (is_array($options) && !empty($options)) {
                                        foreach ($options as $value => $label) {
                                            $selected = $setting['setting_value'] == $value ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($value) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
                                        }
                                    } 
                                    // Specjalne przypadki
                                    else if ($setting['setting_key'] == 'default_order_status') {
                                        $statuses = ['pending' => 'Oczekujące', 'processing' => 'W realizacji', 'shipped' => 'Wysłane', 'delivered' => 'Dostarczone', 'cancelled' => 'Anulowane'];
                                        foreach ($statuses as $value => $label) {
                                            $selected = $setting['setting_value'] == $value ? 'selected' : '';
                                            echo '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
                                        }
                                    }
                                    else if ($setting['setting_key'] == 'smtp_encryption') {
                                        echo '<option value="tls" ' . ($setting['setting_value'] == 'tls' ? 'selected' : '') . '>TLS</option>';
                                        echo '<option value="ssl" ' . ($setting['setting_value'] == 'ssl' ? 'selected' : '') . '>SSL</option>';
                                        echo '<option value="" ' . (empty($setting['setting_value']) ? 'selected' : '') . '>Brak</option>';
                                    }
                                    
                                    echo '</select>';
                                    break;
                                    
                                case 'color':
                                    echo '<input type="color" id="setting_' . $setting['id'] . '" name="settings[' . $setting['setting_key'] . ']" value="' . htmlspecialchars($setting['setting_value']) . '" class="h-10 w-20 rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                                    break;
                                    
                                case 'file':
                                    echo '<div class="flex items-center space-x-4">';
                                    if (!empty($setting['setting_value'])) {
                                        echo '<div class="flex-shrink-0">';
                                        $file_extension = strtolower(pathinfo($setting['setting_value'], PATHINFO_EXTENSION));
                                        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                                            echo '<img src="/' . ltrim($setting['setting_value'], '/') . '" alt="Current file" class="h-16 w-auto object-contain">';
                                        } else {
                                            echo '<div class="h-16 w-16 bg-gray-100 flex items-center justify-center rounded">';
                                            echo '<i class="ri-file-line text-gray-400 text-2xl"></i>';
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                    }
                                    echo '<div class="flex-grow">';
                                    echo '<input type="hidden" name="settings[' . $setting['setting_key'] . ']" value="' . htmlspecialchars($setting['setting_value']) . '">';
                                    echo '<input type="file" id="setting_' . $setting['id'] . '" name="setting_files[' . $setting['setting_key'] . ']" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">';
                                    if (!empty($setting['setting_value'])) {
                                        echo '<p class="mt-1 text-xs text-gray-500">' . htmlspecialchars(basename($setting['setting_value'])) . '</p>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    break;
                                    
                                case 'number':
                                    echo '<input type="number" id="setting_' . $setting['id'] . '" name="settings[' . $setting['setting_key'] . ']" value="' . htmlspecialchars($setting['setting_value']) . '" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                                    break;
                                    
                                case 'email':
                                    echo '<input type="email" id="setting_' . $setting['id'] . '" name="settings[' . $setting['setting_key'] . ']" value="' . htmlspecialchars($setting['setting_value']) . '" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                                    break;
                                    
                                case 'text':
                                default:
                                    echo '<input type="text" id="setting_' . $setting['id'] . '" name="settings[' . $setting['setting_key'] . ']" value="' . htmlspecialchars($setting['setting_value']) . '" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">';
                                    break;
                            }
                            ?>
                            
                            <?php if ($setting['setting_key'] == 'smtp_password'): ?>
                            <p class="mt-1 text-xs text-gray-500">Pozostaw puste, jeśli nie chcesz zmieniać hasła.</p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="flex justify-end mt-6">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                                Zapisz ustawienia
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
