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

// Sprawdzenie uprawnień - tylko admin ma dostęp do zarządzania administratorami
if ($_SESSION['admin_role'] !== 'admin') {
    // Przekierowanie do panelu lub wyświetlenie komunikatu o braku uprawnień
    header("Location: index.php?error=permission");
    exit;
}

$page_title = "Zarządzanie Administratorami";

// Inicjalizacja filtrów
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';

// Obsługa dodawania nowego administratora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    
    // Walidacja
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Nazwa użytkownika jest wymagana.";
    }
    
    if (empty($password)) {
        $errors[] = "Hasło jest wymagane.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Hasło musi mieć co najmniej 8 znaków.";
    }
    
    if (empty($name)) {
        $errors[] = "Imię i nazwisko są wymagane.";
    }
    
    if (empty($email)) {
        $errors[] = "Email jest wymagany.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Podany email jest nieprawidłowy.";
    }
    
    if (!in_array($role, ['admin', 'mechanic', 'owner'])) {
        $errors[] = "Wybrana rola jest nieprawidłowa.";
    }
    
    // Sprawdzenie, czy nazwa użytkownika lub email już istnieją
    $check_query = "SELECT id FROM admins WHERE username = ? OR email = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ss", $username, $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result && $check_result->num_rows > 0) {
        $errors[] = "Nazwa użytkownika lub email już istnieją w systemie.";
    }
    
    if (empty($errors)) {
        // Hash hasła
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Dodanie administratora
        $insert_query = "INSERT INTO admins (username, password, name, email, role) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sssss", $username, $hashed_password, $name, $email, $role);
        
        if ($insert_stmt->execute()) {
            $success_msg = "Administrator został dodany pomyślnie.";
        } else {
            $error_msg = "Błąd podczas dodawania administratora: " . $conn->error;
        }
    } else {
        $error_msg = implode("<br>", $errors);
    }
}

// Obsługa usuwania administratora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $admin_id = (int)$_POST['admin_id'];
    
    // Nie można usunąć samego siebie
    if ($admin_id == $_SESSION['admin_id']) {
        $error_msg = "Nie możesz usunąć swojego własnego konta.";
    } else {
        $delete_query = "DELETE FROM admins WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $admin_id);
        
        if ($delete_stmt->execute()) {
            $success_msg = "Administrator został usunięty.";
        } else {
            $error_msg = "Błąd podczas usuwania administratora: " . $conn->error;
        }
    }
}

// Obsługa zmiany roli administratora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $admin_id = (int)$_POST['admin_id'];
    $new_role = sanitize($_POST['new_role']);
    
    // Walidacja roli
    if (!in_array($new_role, ['admin', 'mechanic', 'owner'])) {
        $error_msg = "Wybrana rola jest nieprawidłowa.";
    } 
    // Nie można zmienić roli samemu sobie (tylko inny admin może to zrobić)
    elseif ($admin_id == $_SESSION['admin_id']) {
        $error_msg = "Nie możesz zmienić roli swojego własnego konta.";
    } else {
        $update_query = "UPDATE admins SET role = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_role, $admin_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Rola administratora została zmieniona.";
        } else {
            $error_msg = "Błąd podczas zmiany roli: " . $conn->error;
        }
    }
}

// Zapytanie podstawowe dla administratorów
$query = "SELECT * FROM admins WHERE 1=1";
$params = [];
$types = "";

// Dodawanie filtrów
if (!empty($search)) {
    $search_term = "%" . $search . "%";
    $query .= " AND (id LIKE ? OR username LIKE ? OR name LIKE ? OR email LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
    $types .= "s";
}

// Sortowanie
$query .= " ORDER BY id ASC";

// Przygotowanie i wykonanie zapytania
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$admins = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
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
                <h1 class="text-2xl font-bold text-gray-800">Zarządzanie Administratorami</h1>
                <p class="text-gray-600">Dodawaj, edytuj i usuwaj konta administratorów</p>
            </div>
            <div class="mt-4 md:mt-0">
                <button type="button" onclick="showAddAdminModal()" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Dodaj administratora
                </button>
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
        
        <!-- Filtry administratorów -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Filtruj administratorów</h2>
            
            <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Szukaj</label>
                    <input type="text" name="search" id="search" value="<?php echo $search; ?>" placeholder="ID, nazwa użytkownika, imię..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rola</label>
                    <select name="role" id="role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">Wszystkie</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="mechanic" <?php echo $role_filter == 'mechanic' ? 'selected' : ''; ?>>Mechanik</option>
                        <option value="owner" <?php echo $role_filter == 'owner' ? 'selected' : ''; ?>>Właściciel</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                        Filtruj
                    </button>
                    <?php if (!empty($search) || !empty($role_filter)): ?>
                    <a href="admin_users.php" class="ml-2 text-gray-600 hover:text-gray-800 py-2 px-4">
                        Wyczyść filtry
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lista administratorów -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Lista administratorów</h2>
            </div>
            
            <?php if (empty($admins)): ?>
            <div class="p-6 text-center">
                <p class="text-gray-500">Brak administratorów spełniających kryteria wyszukiwania.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nazwa użytkownika</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imię i nazwisko</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rola</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($admins as $admin): ?>
                        <tr class="hover:bg-gray-50 <?php echo $admin['id'] == $_SESSION['admin_id'] ? 'bg-blue-50' : ''; ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium">#<?php echo $admin['id']; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($admin['username']); ?>
                                <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                <span class="text-xs text-blue-600 ml-2">(Ty)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($admin['name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo htmlspecialchars($admin['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $role = $admin['role'] ?? 'admin';
                                $role_class = '';
                                $role_text = '';
                                
                                switch ($role) {
                                    case 'admin':
                                        $role_class = 'bg-purple-100 text-purple-800';
                                        $role_text = 'Administrator';
                                        break;
                                    case 'mechanic':
                                        $role_class = 'bg-blue-100 text-blue-800';
                                        $role_text = 'Mechanik';
                                        break;
                                    case 'owner':
                                        $role_class = 'bg-green-100 text-green-800';
                                        $role_text = 'Właściciel';
                                        break;
                                    default:
                                        $role_class = 'bg-gray-100 text-gray-800';
                                        $role_text = ucfirst($role);
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $role_class; ?>">
                                    <?php echo $role_text; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                <!-- Dropdown menu dla akcji -->
                                <div class="relative inline-block text-left" x-data="{ open: false }" id="dropdown-<?php echo $admin['id']; ?>">
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
                                            <!-- Zmiana roli -->
                                            <button @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="showChangeRoleModal(<?php echo $admin['id']; ?>, '<?php echo $admin['role']; ?>')">
                                                Zmień rolę
                                            </button>
                                            
                                            <!-- Usuń administratora -->
                                            <button @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100" onclick="showDeleteModal(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')">
                                                Usuń administratora
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-500">To Ty</span>
                                <?php endif; ?>
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

<!-- Modal dodawania administratora -->
<div id="addAdminModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Dodaj nowego administratora</h3>
            
            <form id="addAdminForm" method="POST" action="">
                <input type="hidden" name="add_admin" value="1">
                
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Nazwa użytkownika</label>
                    <input type="text" name="username" id="username" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                    <input type="password" name="password" id="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                </div>
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko</label>
                    <input type="text" name="name" id="name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                </div>
                
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rola</label>
                    <select name="role" id="admin_role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="admin">Administrator</option>
                        <option value="mechanic">Mechanik</option>
                        <option value="owner">Właściciel</option>
                    </select>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md mr-2" onclick="hideAddAdminModal()">
                        Anuluj
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                        Dodaj
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal zmiany roli -->
<div id="changeRoleModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Zmień rolę administratora</h3>
            
            <form id="changeRoleForm" method="POST" action="">
                <input type="hidden" name="change_role" value="1">
                <input type="hidden" name="admin_id" id="changeRoleAdminId">
                
                <div class="mb-4">
                    <label for="new_role" class="block text-sm font-medium text-gray-700 mb-1">Nowa rola</label>
                    <select name="new_role" id="new_role" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="admin">Administrator</option>
                        <option value="mechanic">Mechanik</option>
                        <option value="owner">Właściciel</option>
                    </select>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md mr-2" onclick="hideChangeRoleModal()">
                        Anuluj
                    </button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                        Zapisz
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal usuwania administratora -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Usuń administratora</h3>
            
            <p class="text-gray-600 mb-4">Czy na pewno chcesz usunąć administratora <span id="deleteAdminName" class="font-semibold"></span>? Tej operacji nie można cofnąć.</p>
            
            <form id="deleteAdminForm" method="POST" action="">
                <input type="hidden" name="delete_admin" value="1">
                <input type="hidden" name="admin_id" id="deleteAdminId">
                
                <div class="flex justify-end mt-6">
                    <button type="button" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md mr-2" onclick="hideDeleteModal()">
                        Anuluj
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-md">
                        Usuń
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Funkcje do obsługi modala dodawania administratora
    function showAddAdminModal() {
        document.getElementById('addAdminModal').classList.remove('hidden');
    }
    
    function hideAddAdminModal() {
        document.getElementById('addAdminModal').classList.add('hidden');
    }
    
    // Funkcje do obsługi modala zmiany roli
    function showChangeRoleModal(adminId, currentRole) {
        document.getElementById('changeRoleAdminId').value = adminId;
        document.getElementById('new_role').value = currentRole;
        document.getElementById('changeRoleModal').classList.remove('hidden');
    }
    
    function hideChangeRoleModal() {
        document.getElementById('changeRoleModal').classList.add('hidden');
    }
    
    // Funkcje do obsługi modala usuwania administratora
    function showDeleteModal(adminId, adminName) {
        document.getElementById('deleteAdminId').value = adminId;
        document.getElementById('deleteAdminName').textContent = adminName;
        document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    
    // Zamykanie modali po kliknięciu poza nimi
    document.querySelectorAll('#addAdminModal, #changeRoleModal, #deleteModal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
