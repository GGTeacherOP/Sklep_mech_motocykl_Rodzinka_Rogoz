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

$page_title = "Lista Administratorów";

// Obsługa dodawania nowego administratora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $email = sanitize($_POST['email']);
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    
    // Walidacja
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email jest wymagany.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Podany email jest nieprawidłowy.";
    }
    
    if (empty($first_name)) {
        $errors[] = "Imię jest wymagane.";
    }
    
    if (empty($last_name)) {
        $errors[] = "Nazwisko jest wymagane.";
    }
    
    if (empty($password)) {
        $errors[] = "Hasło jest wymagane.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Hasło musi mieć co najmniej 8 znaków.";
    }
    
    if (!in_array($role, ['admin', 'mechanic', 'owner'])) {
        $errors[] = "Wybrana rola jest nieprawidłowa.";
    }
    
    // Sprawdzenie, czy email już istnieje
    $check_query = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result && $check_result->num_rows > 0) {
        $errors[] = "Podany email jest już używany w systemie.";
    }
    
    if (empty($errors)) {
        // Hash hasła
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Dodanie nowego administratora do tabeli users
        $insert_query = "INSERT INTO users (email, first_name, last_name, password, role) VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("sssss", $email, $first_name, $last_name, $hashed_password, $role);
        
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
        // Usunięcie administratora (ustawienie roli na 'user')
        $update_query = "UPDATE users SET role = 'user' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $admin_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Administrator został zdegradowany do zwykłego użytkownika.";
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
        $update_query = "UPDATE users SET role = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_role, $admin_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Rola administratora została zmieniona.";
        } else {
            $error_msg = "Błąd podczas zmiany roli: " . $conn->error;
        }
    }
}

// Pobieranie listy administratorów
$query = "SELECT * FROM users WHERE role = 'admin' ORDER BY id";
$result = $conn->query($query);
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
                <h1 class="text-2xl font-bold text-gray-800">Lista Administratorów</h1>
                <p class="text-gray-600">Zarządzaj użytkownikami z uprawnieniami administracyjnymi</p>
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
        
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Lista administratorów</h2>
            </div>
            
            <?php if (empty($admins)): ?>
            <div class="p-6">
                <p class="text-gray-500 text-center">Brak administratorów w systemie.</p>
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data rejestracji</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($admins as $admin): ?>
                        <tr class="hover:bg-gray-50 <?php echo $admin['id'] == $_SESSION['admin_id'] ? 'bg-blue-50' : ''; ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium">#<?php echo $admin['id']; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($admin['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $role = $admin['role'];
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
                                        $role_text = ucfirst($role);
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $role_class; ?>">
                                    <?php echo $role_text; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d.m.Y H:i', strtotime($admin['created_at'])); ?>
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
                                            <a href="user-details.php?id=<?php echo $admin['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Szczegóły użytkownika
                                            </a>
                                            
                                            <!-- Zmiana roli -->
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <div class="px-4 py-2">
                                                <p class="text-xs text-gray-500 mb-1">Zmień rolę:</p>
                                                <div class="grid grid-cols-3 gap-1">
                                                    <?php if ($admin['role'] != 'admin'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                        <input type="hidden" name="new_role" value="admin">
                                                        <button type="submit" @click="open = false" class="w-full text-center px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 hover:bg-blue-200">
                                                            Admin
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($admin['role'] != 'mechanic'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                        <input type="hidden" name="new_role" value="mechanic">
                                                        <button type="submit" @click="open = false" class="w-full text-center px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                                            Mechanik
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($admin['role'] != 'owner'): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="change_role" value="1">
                                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                        <input type="hidden" name="new_role" value="owner">
                                                        <button type="submit" @click="open = false" class="w-full text-center px-2 py-1 text-xs rounded bg-purple-100 text-purple-800 hover:bg-purple-200">
                                                            Właściciel
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Usunięcie administratora -->
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <form method="POST" action="" onsubmit="return confirm('Czy na pewno chcesz zdegradować tego administratora do zwykłego użytkownika?');">
                                                <input type="hidden" name="delete_admin" value="1">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                                <button type="submit" @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-gray-100">
                                                    Usuń uprawnienia
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <span class="text-gray-400">To Ty</span>
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
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Imię</label>
                        <input type="text" id="first_name" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko</label>
                        <input type="text" id="last_name" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                    <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rola</label>
                    <select id="role" name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="admin">Administrator</option>
                        <option value="mechanic">Mechanik</option>
                        <option value="owner">Właściciel</option>
                    </select>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="button" onclick="hideAddAdminModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md mr-2">
                        Anuluj
                    </button>
                    <input type="hidden" name="add_admin" value="1">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md">
                        Dodaj administratora
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showAddAdminModal() {
        document.getElementById('addAdminModal').classList.remove('hidden');
    }
    
    function hideAddAdminModal() {
        document.getElementById('addAdminModal').classList.add('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
