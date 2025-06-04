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

// Sprawdzenie uprawnień - tylko admin ma dostęp do zarządzania użytkownikami
if ($_SESSION['admin_role'] !== 'admin') {
    // Przekierowanie do panelu lub wyświetlenie komunikatu o braku uprawnień
    header("Location: index.php?error=permission");
    exit;
}

$page_title = "Zarządzanie Użytkownikami";

// Inicjalizacja filtrów
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Obsługa blokowania/odblokowania użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = sanitize($_POST['new_status']);
    
    // Sprawdzenie, czy status jest prawidłowy
    if ($new_status == 'active' || $new_status == 'blocked') {
        $update_query = "UPDATE users SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $user_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Status użytkownika został zmieniony.";
        } else {
            $error_msg = "Błąd podczas zmiany statusu: " . $conn->error;
        }
    } else {
        $error_msg = "Nieprawidłowy status.";
    }
}

// Obsługa zmiany roli użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = sanitize($_POST['new_role']);
    
    // Sprawdzenie jakie wartości są dozwolone w kolumnie 'role'
    $check_column_query = "SHOW COLUMNS FROM users LIKE 'role'";
    $column_result = $conn->query($check_column_query);
    $allowed_roles = [];
    
    if ($column_result && $column_result->num_rows > 0) {
        $column_info = $column_result->fetch_assoc();
        // Wyciągnięcie dozwolonych wartości z definicji ENUM
        if (preg_match("/^enum\((.*)\)$/", $column_info['Type'], $matches)) {
            $values = str_getcsv($matches[1], ',', "'");
            $allowed_roles = array_map('trim', $values);
            error_log("Dozwolone role: " . implode(", ", $allowed_roles));
        }
    }
    
    // Jeśli nie znaleziono kolumny 'role' lub nie ma zdefiniowanych wartości
    if (empty($allowed_roles)) {
        // Kolumna nie istnieje lub nie ma zdefiniowanych wartości ENUM
        $add_column_query = "ALTER TABLE users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'";
        if ($conn->query($add_column_query)) {
            $success_msg = "Kolumna 'role' została dodana do tabeli 'users'. Spróbuj ponownie zmienić rolę.";
        } else {
            $error_msg = "Błąd podczas dodawania/modyfikacji kolumny 'role': " . $conn->error;
        }
    } else {
        // Sprawdzenie, czy rola jest prawidłowa
        if (in_array($new_role, $allowed_roles)) {
            // Logowanie danych przed aktualizacją
            error_log("Zmiana roli - User ID: " . $user_id . ", Nowa rola: " . $new_role);
            
            $update_query = "UPDATE users SET role = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $new_role, $user_id);
            
            if ($update_stmt->execute()) {
                $rows_affected = $update_stmt->affected_rows;
                $success_msg = "Rola użytkownika została zmieniona. (Zaktualizowano rekordów: " . $rows_affected . ")";
                
                // Sprawdzenie czy faktycznie nastąpiła zmiana
                $verify_query = "SELECT role FROM users WHERE id = ?";
                $verify_stmt = $conn->prepare($verify_query);
                $verify_stmt->bind_param("i", $user_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                $user_after = $verify_result->fetch_assoc();
                $success_msg .= " Nowa rola: " . ($user_after['role'] ?? 'brak');
            } else {
                $error_msg = "Błąd podczas zmiany roli: " . $conn->error;
            }
        } else {
            // Przypadek gdy wybrana rola nie jest dozwolona
            error_log("Próba ustawienia niedozwolonej roli: " . $new_role);
            
            // Próba modyfikacji kolumny 'role' aby umożliwić nowe wartości
            $alter_query = "";
            
            // Jeśli kolumna jest typu ENUM, modyfikujemy ją
            if (strpos($column_info['Type'], 'enum') === 0) {
                $new_values = array_merge($allowed_roles, ['user', 'admin', 'mechanic', 'owner']);
                $new_values = array_unique($new_values);
                $enum_values = "'" . implode("','" , $new_values) . "'";
                $alter_query = "ALTER TABLE users MODIFY COLUMN role ENUM($enum_values) NOT NULL DEFAULT 'user'";
            } else {
                // Jeśli kolumna nie jest typu ENUM, zmieniamy ją na VARCHAR
                $alter_query = "ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'user'";
            }
            
            // Próba modyfikacji kolumny
            if (!empty($alter_query) && $conn->query($alter_query)) {
                $update_query = "UPDATE users SET role = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $new_role, $user_id);
                
                if ($update_stmt->execute()) {
                    $success_msg = "Kolumna 'role' została zaktualizowana i rola użytkownika zmieniona na: " . $new_role;
                } else {
                    $error_msg = "Błąd podczas zmiany roli: " . $conn->error;
                }
            } else {
                $error_msg = "Nieprawidłowa rola. Dozwolone wartości: " . implode(", ", $allowed_roles);
            }
        }
    }
}

// Zapytanie podstawowe
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = "";

// Dodawanie filtrów
if (!empty($search)) {
    $search_term = "%" . $search . "%";
    $query .= " AND (id LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Sortowanie
$query .= " ORDER BY id DESC";

// Przygotowanie i wykonanie zapytania
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Główna zawartość -->
    <div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Zarządzanie Użytkownikami</h1>
                <p class="text-gray-600">Przeglądaj i zarządzaj kontami użytkowników</p>
            </div>
        </div>
        
        <?php if (isset($success_msg)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo $success_msg; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_msg)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error_msg; ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Filtry użytkowników -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Filtruj użytkowników</h2>
            
            <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Szukaj</label>
                    <input type="text" name="search" id="search" value="<?php echo $search; ?>" placeholder="ID, email, imię, nazwisko..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">Wszystkie</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Aktywne</option>
                        <option value="blocked" <?php echo $status_filter == 'blocked' ? 'selected' : ''; ?>>Zablokowane</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                        Filtruj
                    </button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="users.php" class="ml-2 text-gray-600 hover:text-gray-800 py-2 px-4">
                        Wyczyść filtry
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lista użytkowników -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Lista użytkowników</h2>
            </div>
            
            <?php if (empty($users)): ?>
            <div class="p-6 text-center">
                <p class="text-gray-500">Brak użytkowników spełniających kryteria wyszukiwania.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imię i Nazwisko</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rola</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data rejestracji</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium">#<?php echo $user['id']; ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <?php 
                                    if (!empty($user['first_name']) && !empty($user['last_name'])) {
                                        echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
                                    } else {
                                        echo '<span class="text-gray-500">Brak danych</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $role = $user['role'] ?? 'user';
                                $role_class = '';
                                $role_text = '';
                                
                                switch ($role) {
                                    case 'admin':
                                        $role_class = 'bg-blue-100 text-blue-800';
                                        $role_text = 'Administrator';
                                        break;
                                    case 'mechanic':
                                        $role_class = 'bg-yellow-100 text-yellow-800';
                                        $role_text = 'Mechanik';
                                        break;
                                    case 'owner':
                                        $role_class = 'bg-purple-100 text-purple-800';
                                        $role_text = 'Właściciel';
                                        break;
                                    default:
                                        $role_class = 'bg-gray-100 text-gray-800';
                                        $role_text = 'Użytkownik';
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $role_class; ?>">
                                    <?php echo $role_text; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status = $user['status'] ?? 'active';
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($status) {
                                    case 'active':
                                        $status_class = 'bg-green-100 text-green-800';
                                        $status_text = 'Aktywny';
                                        break;
                                    case 'blocked':
                                        $status_class = 'bg-red-100 text-red-800';
                                        $status_text = 'Zablokowany';
                                        break;
                                    default:
                                        $status_class = 'bg-gray-100 text-gray-800';
                                        $status_text = ucfirst($status);
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <!-- Dropdown menu dla akcji -->
                                <div class="relative inline-block text-left" x-data="{ open: false }" id="dropdown-<?php echo $user['id']; ?>">
                                    <div>
                                        <button @click="open = !open" type="button" class="inline-flex justify-center w-full rounded-md px-2 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Akcje
                                            <svg class="-mr-1 ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                    
                                    <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                                        <div class="py-1">
                                            <a href="user-details.php?id=<?php echo $user['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Szczegóły użytkownika
                                            </a>
                                            
                                            <a href="user-orders.php?user_id=<?php echo $user['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Zamówienia użytkownika
                                            </a>
                                            
                                            <?php if (!isset($user['status']) || $user['status'] == 'active' || $user['status'] == ''): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="blocked">
                                                <button type="submit" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                                    Zablokuj użytkownika
                                                </button>
                                            </form>
                                            <?php elseif (isset($user['status']) && $user['status'] == 'blocked'): ?>
                                            <form method="POST" action="">
                                                <input type="hidden" name="toggle_status" value="1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="new_status" value="active">
                                                <button type="submit" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-gray-100">
                                                    Odblokuj użytkownika
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <!-- Zmiana roli użytkownika -->
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <div class="px-4 py-2">
                                                <p class="text-xs text-gray-500 mb-1">Zmień rolę:</p>
                                                <div class="grid grid-cols-2 gap-1">
                                                    <?php if (!isset($user['role']) || $user['role'] != 'admin'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="new_role" value="admin">
                                                        <button type="submit" @click="open = false" class="w-full text-center px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 hover:bg-blue-200">
                                                            Administrator
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!isset($user['role']) || $user['role'] != 'mechanic'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="new_role" value="mechanic">
                                                        <button type="submit" @click="open = false" class="w-full text-center px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                                            Mechanik
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!isset($user['role']) || $user['role'] != 'owner'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="new_role" value="owner">
                                                        <button type="submit" @click="open = false" class="w-full text-center px-2 py-1 text-xs rounded bg-purple-100 text-purple-800 hover:bg-purple-200">
                                                            Właściciel
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!isset($user['role']) || $user['role'] != 'user'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="new_role" value="user">
                                                        <button type="submit" @click="open = false" class="w-full text-center px-2 py-1 text-xs rounded bg-gray-100 text-gray-800 hover:bg-gray-200">
                                                            Użytkownik
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
