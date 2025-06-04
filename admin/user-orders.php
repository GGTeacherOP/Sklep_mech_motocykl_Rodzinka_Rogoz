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
// Mechanicy również mają dostęp do zamówień, aby mogli obsługiwać serwis
if ($_SESSION['admin_role'] !== 'admin' && $_SESSION['admin_role'] !== 'mechanic') {
    header("Location: index.php?error=permission");
    exit;
}

$page_title = "Zamówienia użytkownika";

// Sprawdzenie, czy podano ID użytkownika
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: users.php");
    exit;
}

$user_id = (int)$_GET['user_id'];

// Pobieranie danych użytkownika
$user_query = "SELECT id, first_name, last_name, email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
} else {
    header("Location: users.php?error=user_not_found");
    exit;
}

// Inicjalizacja filtrów
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Zapytanie podstawowe
$query = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];
$types = "i";

// Dodawanie filtrów
if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND created_at >= ?";
    $date_from = date('Y-m-d 00:00:00', strtotime($date_from));
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND created_at <= ?";
    $date_to = date('Y-m-d 23:59:59', strtotime($date_to));
    $params[] = $date_to;
    $types .= "s";
}

// Sortowanie
$query .= " ORDER BY created_at DESC";

// Przygotowanie i wykonanie zapytania
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Sprawdzenie czy tabela orders istnieje
$check_table_query = "SHOW TABLES LIKE 'orders'";
$table_result = $conn->query($check_table_query);
$orders_table_exists = ($table_result && $table_result->num_rows > 0);

// Pobieranie statystyk zamówień użytkownika tylko jeśli tabela orders istnieje
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
                MIN(created_at) as first_order_date,
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
            'first_order_date' => null,
            'last_order_date' => null
        ];
    }
} else {
    // Tabela orders nie istnieje
    $stats = [
        'total_orders' => 0,
        'total_spent' => 0,
        'first_order_date' => null,
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
                <h1 class="text-2xl font-bold text-gray-800">Zamówienia użytkownika</h1>
                <p class="text-gray-600">
                    <a href="user-details.php?id=<?php echo $user_id; ?>" class="text-blue-600 hover:text-blue-800">
                        <i class="ri-arrow-left-line align-bottom"></i> Powrót do szczegółów użytkownika
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Informacje o użytkowniku -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Dane użytkownika</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">ID użytkownika</h3>
                        <p class="text-lg font-bold">#<?php echo $user['id']; ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Imię i nazwisko</h3>
                        <p class="text-lg font-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Email</h3>
                        <p class="text-lg font-bold"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statystyki zamówień -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-sm font-medium text-gray-500">Liczba zamówień</h3>
                <p class="text-2xl font-bold mt-1"><?php echo $stats['total_orders'] ?? 0; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-sm font-medium text-gray-500">Wydane pieniądze</h3>
                <p class="text-2xl font-bold mt-1"><?php echo number_format(($stats['total_spent'] ?? 0), 2, ',', ' '); ?> zł</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-sm font-medium text-gray-500">Pierwsze zamówienie</h3>
                <p class="text-lg font-bold mt-1">
                    <?php echo $stats['first_order_date'] ? date('d.m.Y', strtotime($stats['first_order_date'])) : 'Brak'; ?>
                </p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-sm font-medium text-gray-500">Ostatnie zamówienie</h3>
                <p class="text-lg font-bold mt-1">
                    <?php echo $stats['last_order_date'] ? date('d.m.Y', strtotime($stats['last_order_date'])) : 'Brak'; ?>
                </p>
            </div>
        </div>
        
        <!-- Filtry zamówień -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="p-6">
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status zamówienia</label>
                        <select id="status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Wszystkie statusy</option>
                            <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>Nowe</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>W realizacji</option>
                            <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Wysłane</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Zakończone</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Anulowane</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Od daty</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from ? date('Y-m-d', strtotime($date_from)) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Do daty</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to ? date('Y-m-d', strtotime($date_to)) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Filtruj
                        </button>
                        
                        <?php if (!empty($status_filter) || !empty($date_from) || !empty($date_to)): ?>
                        <a href="user-orders.php?user_id=<?php echo $user_id; ?>" class="ml-2 text-gray-500 hover:text-gray-700 font-medium py-2 px-4 border border-gray-300 rounded">
                            Wyczyść
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista zamówień -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Lista zamówień</h2>
            </div>
            <div class="overflow-x-auto">
                <?php if (count($orders) > 0): ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sposób płatności</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sposób dostawy</th>
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
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($order['payment_method'] ?? 'Nieznany'); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($order['shipping_method'] ?? 'Nieznany'); ?></span>
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
