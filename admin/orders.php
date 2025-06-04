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

$page_title = "Zarządzanie Zamówieniami";

// Inicjalizacja filtrów
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Budowanie zapytania z filtrami
$query = "SELECT o.*, u.email, u.first_name, u.last_name 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND o.created_at >= ?";
    $params[] = $date_from . " 00:00:00";
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND o.created_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%" . $search . "%";
    $query .= " AND (o.id LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.shipping_address LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sssss";
}

// Sortowanie i limit
$query .= " ORDER BY o.id DESC";

// Przygotowanie i wykonanie zapytania
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Obsługa zmiany statusu zamówienia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['status']);
    
    // Weryfikacja, czy status jest jedną z dozwolonych wartości
    $allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    
    if (!in_array($new_status, $allowed_statuses)) {
        $error_msg = "Nieprawidłowa wartość statusu. Dozwolone wartości: " . implode(', ', $allowed_statuses);
    } else {
        // Pobieramy najpierw aktualny status, aby sprawdzić czy kolumna status ma odpowiedni format
        $check_query = "SELECT status FROM orders WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result && $check_result->num_rows > 0) {
            $current_status = $check_result->fetch_assoc()['status'];
            
            // Aktualizacja statusu
            $update_query = "UPDATE orders SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $new_status, $order_id);
            
            if ($update_stmt->execute()) {
                $success_msg = "Status zamówienia #$order_id został zaktualizowany z '$current_status' na '$new_status'.";
                
                // Aktualizacja statusu w tablicy zamówień
                foreach ($orders as &$order) {
                    if ($order['id'] == $order_id) {
                        $order['status'] = $new_status;
                        break;
                    }
                }
            } else {
                $error_msg = "Błąd podczas aktualizacji statusu: " . $conn->error;
            }
        } else {
            $error_msg = "Nie można znaleźć zamówienia o ID: $order_id";
        }
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
                <h1 class="text-2xl font-bold text-gray-800">Zarządzanie Zamówieniami</h1>
                <p class="text-gray-600">Przeglądaj i zarządzaj zamówieniami klientów</p>
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
        
        <!-- Filtry zamówień -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Filtruj zamówienia</h2>
            
            <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">Wszystkie</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Oczekujące</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>W trakcie realizacji</option>
                        <option value="shipped" <?php echo $status_filter == 'shipped' ? 'selected' : ''; ?>>Wysłane</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Zakończone</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Anulowane</option>
                    </select>
                </div>
                
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Data od</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo $date_from; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Data do</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo $date_to; ?>" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Szukaj</label>
                    <input type="text" name="search" id="search" value="<?php echo $search; ?>" placeholder="ID, email, nazwisko..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                        Filtruj
                    </button>
                    <?php if (!empty($status_filter) || !empty($date_from) || !empty($date_to) || !empty($search)): ?>
                    <a href="orders.php" class="ml-2 text-gray-600 hover:text-gray-800 py-2 px-4">
                        Wyczyść filtry
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Lista zamówień -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Lista zamówień</h2>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="p-6 text-center">
                <p class="text-gray-500">Brak zamówień spełniających kryteria wyszukiwania.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klient</th>
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
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <?php 
                                    if (!empty($order['first_name']) && !empty($order['last_name'])) {
                                        echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                        echo '<div class="text-xs text-gray-500">' . htmlspecialchars($order['email']) . '</div>';
                                    } else {
                                        echo htmlspecialchars($order['email'] ?? 'Brak danych');
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status = $order['status'] ?? 'pending';
                                $status_class = '';
                                $status_text = '';
                                
                                switch ($status) {
                                    case 'pending':
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        $status_text = 'Oczekujące';
                                        break;
                                    case 'processing':
                                        $status_class = 'bg-blue-100 text-blue-800';
                                        $status_text = 'W realizacji';
                                        break;
                                    case 'shipped':
                                        $status_class = 'bg-purple-100 text-purple-800';
                                        $status_text = 'Wysłane';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-green-100 text-green-800';
                                        $status_text = 'Zakończone';
                                        break;
                                    case 'cancelled':
                                        $status_class = 'bg-red-100 text-red-800';
                                        $status_text = 'Anulowane';
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <!-- Dropdown menu dla akcji -->
                                <div class="relative inline-block text-left" x-data="{ open: false }" id="dropdown-<?php echo $order['id']; ?>">
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
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Szczegóły zamówienia
                                            </a>
                                            
                                            <!-- Zmiana statusu -->
                                            <button @click="open = false" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="showStatusModal(<?php echo $order['id']; ?>, '<?php echo $status; ?>')">
                                                Zmień status
                                            </button>
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

<!-- Modal zmiany statusu -->
<div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Zmiana statusu zamówienia</h3>
            
            <form id="updateStatusForm" method="POST" action="">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="update_status" value="1">
                
                <div class="mb-4">
                    <label for="modalStatus" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="modalStatus" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="pending">Oczekujące</option>
                        <option value="processing">W trakcie realizacji</option>
                        <option value="shipped">Wysłane</option>
                        <option value="completed">Zakończone</option>
                        <option value="cancelled">Anulowane</option>
                    </select>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-md mr-2" onclick="hideStatusModal()">
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

<script>
    // Funkcje do obsługi modala zmiany statusu
    function showStatusModal(orderId, currentStatus) {
        document.getElementById('modalOrderId').value = orderId;
        document.getElementById('modalStatus').value = currentStatus;
        document.getElementById('statusModal').classList.remove('hidden');
    }
    
    function hideStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
    }
    
    // Zamykanie modala po kliknięciu poza nim
    document.getElementById('statusModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideStatusModal();
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
