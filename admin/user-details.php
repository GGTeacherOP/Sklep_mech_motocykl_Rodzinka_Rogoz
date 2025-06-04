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

$page_title = "Szczegóły użytkownika";
$error_msg = '';
$success_msg = '';

// Sprawdzenie, czy podano ID użytkownika
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = (int)$_GET['id'];

// Obsługa aktualizacji danych użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $first_name = sanitize($_POST['first_name']);
        $last_name = sanitize($_POST['last_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone'] ?? '');
        $status = sanitize($_POST['status']);
        
        // Walidacja podstawowa
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_msg = "Imię, nazwisko i email są wymagane.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Podany adres email jest nieprawidłowy.";
        } else {
            // Sprawdzenie, czy email nie jest już używany przez innego użytkownika
            $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result && $check_result->num_rows > 0) {
                $error_msg = "Podany adres email jest już używany przez innego użytkownika.";
            } else {
                // Aktualizacja danych użytkownika
                $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, status = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("sssssi", $first_name, $last_name, $email, $phone, $status, $user_id);
                
                if ($update_stmt->execute()) {
                    $success_msg = "Dane użytkownika zostały zaktualizowane.";
                } else {
                    $error_msg = "Błąd podczas aktualizacji danych: " . $conn->error;
                }
            }
        }
    } elseif (isset($_POST['reset_password'])) {
        // Generowanie nowego hasła
        $new_password = bin2hex(random_bytes(6)); // 12 znaków
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Aktualizacja hasła w bazie
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Hasło zostało zresetowane. Nowe hasło: <strong>" . $new_password . "</strong>";
        } else {
            $error_msg = "Błąd podczas resetowania hasła: " . $conn->error;
        }
    }
}

// Pobieranie danych użytkownika
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    header("Location: users.php?error=user_not_found");
    exit;
}

// Pobieranie zamówień użytkownika
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders = [];

if ($orders_result && $orders_result->num_rows > 0) {
    while ($order_row = $orders_result->fetch_assoc()) {
        $orders[] = $order_row;
    }
}

// Sprawdzenie czy tabela orders istnieje
$check_table_query = "SHOW TABLES LIKE 'orders'";
$table_result = $conn->query($check_table_query);
$orders_table_exists = ($table_result && $table_result->num_rows > 0);

// Pobieranie statystyk użytkownika tylko jeśli tabela orders istnieje
if ($orders_table_exists) {
    // Sprawdzenie, jakie kolumny ma tabela orders
    $check_columns_query = "SHOW COLUMNS FROM orders";
    $columns_result = $conn->query($check_columns_query);
    $has_user_id = false;
    $column_name = 'user_id'; // domyślna nazwa kolumny
    
    if ($columns_result && $columns_result->num_rows > 0) {
        while ($column = $columns_result->fetch_assoc()) {
            if ($column['Field'] == 'user_id' || $column['Field'] == 'customer_id') {
                $has_user_id = true;
                $column_name = $column['Field'];
                break;
            }
        }
    }
    
    if ($has_user_id) {
        $stats_query = "
            SELECT 
                COUNT(id) as total_orders,
                IFNULL(SUM(total), 0) as total_spent,
                MAX(created_at) as last_order_date
            FROM orders 
            WHERE $column_name = ?
        ";
        $stats_stmt = $conn->prepare($stats_query);
        $stats_stmt->bind_param("i", $user_id);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $stats = $stats_result->fetch_assoc();
    } else {
        // Brak kolumny user_id lub customer_id
        $stats = [
            'total_orders' => 0,
            'total_spent' => 0,
            'last_order_date' => null
        ];
    }
} else {
    // Tabela orders nie istnieje
    $stats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'last_order_date' => null
    ];
}

include 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Główna zawartość -->
    <div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Szczegóły użytkownika</h1>
                <p class="text-gray-600">
                    <a href="users.php" class="text-blue-600 hover:text-blue-800">
                        <i class="ri-arrow-left-line align-bottom"></i> Powrót do listy użytkowników
                    </a>
                </p>
            </div>
        </div>
        
        <?php if (!empty($error_msg)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo $error_msg; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo $success_msg; ?></p>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Informacje o użytkowniku -->
            <div class="col-span-2">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold">Dane użytkownika</h2>
                    </div>
                    <div class="p-6">
                        <form method="POST" action="">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Imię</label>
                                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko</label>
                                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adres email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status konta</label>
                                <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    <option value="active" <?php echo (!isset($user['status']) || $user['status'] == 'active') ? 'selected' : ''; ?>>Aktywne</option>
                                    <option value="blocked" <?php echo (isset($user['status']) && $user['status'] == 'blocked') ? 'selected' : ''; ?>>Zablokowane</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label for="registered_at" class="block text-sm font-medium text-gray-700 mb-1">Data rejestracji</label>
                                <input type="text" id="registered_at" value="<?php echo htmlspecialchars($user['created_at']); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                            </div>
                            
                            <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                                <input type="hidden" name="update_user" value="1">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Zapisz zmiany
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Resetowanie hasła -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold">Bezpieczeństwo</h2>
                    </div>
                    <div class="p-6">
                        <form method="POST" action="" onsubmit="return confirm('Czy na pewno chcesz zresetować hasło tego użytkownika? Nowe hasło zostanie wygenerowane automatycznie.');">
                            <p class="mb-4 text-gray-600">Zresetuj hasło użytkownika, aby wygenerować nowe, tymczasowe hasło.</p>
                            <input type="hidden" name="reset_password" value="1">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Zresetuj hasło
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Statystyki użytkownika -->
            <div class="col-span-1">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold">Statystyki</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">ID użytkownika</h3>
                                <p class="text-2xl font-bold">#<?php echo $user['id']; ?></p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Liczba zamówień</h3>
                                <p class="text-2xl font-bold"><?php echo $stats['total_orders'] ?? 0; ?></p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Wydane pieniądze</h3>
                                <p class="text-2xl font-bold"><?php echo number_format(($stats['total_spent'] ?? 0), 2, ',', ' '); ?> zł</p>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-500">Ostatnie zamówienie</h3>
                                <p class="text-lg font-bold">
                                    <?php echo $stats['last_order_date'] ? date('d.m.Y H:i', strtotime($stats['last_order_date'])) : 'Brak zamówień'; ?>
                                </p>
                            </div>
                            <div class="pt-2">
                                <a href="user-orders.php?user_id=<?php echo $user['id']; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                    Zobacz wszystkie zamówienia <i class="ri-arrow-right-line ml-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold">Status</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!isset($user['status']) || $user['status'] == 'active'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span> Aktywne
                            </span>
                            <p class="mt-2 text-sm text-gray-600">Konto użytkownika jest aktywne i może się zalogować.</p>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span> Zablokowane
                            </span>
                            <p class="mt-2 text-sm text-gray-600">Konto użytkownika jest zablokowane i nie może się zalogować.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ostatnie zamówienia -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Ostatnie zamówienia</h2>
            </div>
            <div class="overflow-x-auto">
                <?php if (count($orders) > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kwota</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium">#<?php echo $order['id']; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900"><?php echo number_format($order['total'], 2, ',', ' '); ?> zł</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_classes = [
                                    'new' => 'bg-blue-100 text-blue-800',
                                    'processing' => 'bg-yellow-100 text-yellow-800',
                                    'shipped' => 'bg-purple-100 text-purple-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                ];
                                $status_text = [
                                    'new' => 'Nowe',
                                    'processing' => 'W realizacji',
                                    'shipped' => 'Wysłane',
                                    'completed' => 'Zakończone',
                                    'cancelled' => 'Anulowane',
                                ];
                                $status = $order['status'] ?? 'new';
                                $class = $status_classes[$status] ?? 'bg-gray-100 text-gray-800';
                                $text = $status_text[$status] ?? 'Nieznany';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $class; ?>">
                                    <?php echo $text; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:text-blue-900">Szczegóły</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="p-6">
                    <p class="text-gray-500 text-center">Brak zamówień dla tego użytkownika.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
