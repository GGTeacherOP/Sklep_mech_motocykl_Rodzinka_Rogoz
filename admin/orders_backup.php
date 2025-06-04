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

$page_title = "Zarządzanie zamówieniami";

// Parametry filtrowania
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Parametry sortowania
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'date';
$order = isset($_GET['order']) ? sanitize($_GET['order']) : 'desc';

// Aktualizacja statusu zamówienia
$message = '';
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = sanitize($_POST['status']);
    
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $message = 'Status zamówienia został zaktualizowany.';
    } else {
        $message = 'Wystąpił błąd podczas aktualizacji statusu zamówienia.';
    }
}

// Budowanie zapytania SQL
$query = "SELECT o.*, u.email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

// Dodawanie filtrów do zapytania
if (!empty($status_filter)) {
    $query .= " AND o.status = '$status_filter'";
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= '$date_from'";
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= '$date_to'";
}

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE '%$search%' OR u.email LIKE '%$search%' OR o.billing_name LIKE '%$search%')";
}

// Dodawanie sortowania
switch ($sort) {
    case 'number':
        $query .= " ORDER BY o.order_number " . ($order === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'customer':
        $query .= " ORDER BY o.billing_name " . ($order === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'total':
        $query .= " ORDER BY o.total_amount " . ($order === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'status':
        $query .= " ORDER BY o.status " . ($order === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'date':
    default:
        $query .= " ORDER BY o.created_at " . ($order === 'asc' ? 'ASC' : 'DESC');
}

// Liczenie wszystkich wierszy spełniających kryteria
$count_query = str_replace("o.*, u.email", "COUNT(*) as total", $query);
$count_parts = explode(' ORDER BY ', $count_query);
$count_query = $count_parts[0];

$count_result = $conn->query($count_query);
$total_rows = 0;

if ($count_result && $count_result->num_rows > 0) {
    $total_rows = $count_result->fetch_assoc()['total'];
}

// Obliczanie stron
$total_pages = ceil($total_rows / $per_page);
$page = max(1, min($page, $total_pages)); // Zabezpieczenie przed złymi wartościami strony
$offset = ($page - 1) * $per_page;

// Dodawanie limitów do zapytania
$query .= " LIMIT $offset, $per_page";

// Pobieranie zamówień
$orders_result = $conn->query($query);
$orders = [];

if ($orders_result && $orders_result->num_rows > 0) {
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Dodawanie filtrów
if (!empty($status_filter)) {
    $query .= " AND o.status = '$status_filter'";
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.order_date) >= '$date_from'";
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.order_date) <= '$date_to'";
}

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE '%$search%' OR o.first_name LIKE '%$search%' OR o.last_name LIKE '%$search%' OR o.email LIKE '%$search%')";
}

// Dodawanie sortowania
$valid_sort_fields = ['date' => 'o.order_date', 'number' => 'o.order_number', 'status' => 'o.status', 'total' => 'o.total'];
$valid_order_values = ['asc', 'desc'];

$sort_field = isset($valid_sort_fields[$sort]) ? $valid_sort_fields[$sort] : $valid_sort_fields['date'];
$order_value = in_array(strtolower($order), $valid_order_values) ? strtolower($order) : 'desc';

$query .= " ORDER BY $sort_field $order_value";

// Obliczanie całkowitej liczby wyników dla paginacji
$count_query = str_replace("SELECT o.*, u.email", "SELECT COUNT(*) as total", $query);
$count_query = preg_replace('/ORDER BY.*$/i', '', $count_query);

$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Ograniczenie wyników do bieżącej strony
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $offset, $per_page";

// Wykonanie zapytania
$result = $conn->query($query);
$orders = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Zarządzanie zamówieniami</h1>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="bg-green-50 text-green-800 rounded-lg p-4 mb-6">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
    
    <!-- Filtry -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="" method="get" class="space-y-4 md:space-y-0 md:flex md:flex-wrap md:items-end md:space-x-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Oczekujące</option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>W realizacji</option>
                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Wysłane</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Zrealizowane</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Anulowane</option>
                </select>
            </div>
            
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Data od</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Data do</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex-grow">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Szukaj</label>
                <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="Numer zamówienia, imię, nazwisko, email..." class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex space-x-2">
                <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                <input type="hidden" name="order" value="<?php echo $order; ?>">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
                    Filtruj
                </button>
                <a href="orders.php" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Lista zamówień -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($orders)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Brak zamówień spełniających kryteria wyszukiwania.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'number', 'order' => ($sort === 'number' && $order === 'asc') ? 'desc' : 'asc'])); ?>" class="flex items-center">
                                Numer zamówienia
                                <?php if ($sort === 'number'): ?>
                                <i class="ri-arrow-<?php echo $order === 'asc' ? 'up' : 'down'; ?>-s-line ml-1"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'date', 'order' => ($sort === 'date' && $order === 'asc') ? 'desc' : 'asc'])); ?>" class="flex items-center">
                                Data
                                <?php if ($sort === 'date'): ?>
                                <i class="ri-arrow-<?php echo $order === 'asc' ? 'up' : 'down'; ?>-s-line ml-1"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Klient
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'status', 'order' => ($sort === 'status' && $order === 'asc') ? 'desc' : 'asc'])); ?>" class="flex items-center">
                                Status
                                <?php if ($sort === 'status'): ?>
                                <i class="ri-arrow-<?php echo $order === 'asc' ? 'up' : 'down'; ?>-s-line ml-1"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'total', 'order' => ($sort === 'total' && $order === 'asc') ? 'desc' : 'asc'])); ?>" class="flex items-center">
                                Kwota
                                <?php if ($sort === 'total'): ?>
                                <i class="ri-arrow-<?php echo $order === 'asc' ? 'up' : 'down'; ?>-s-line ml-1"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Akcje
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($orders as $order): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $order['order_number']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $order['email']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($order['status']) {
                                case 'pending':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Oczekujące';
                                    break;
                                case 'processing':
                                    $status_class = 'bg-blue-100 text-blue-800';
                                    $status_text = 'W realizacji';
                                    break;
                                case 'shipped':
                                    $status_class = 'bg-indigo-100 text-indigo-800';
                                    $status_text = 'Wysłane';
                                    break;
                                case 'completed':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Zrealizowane';
                                    break;
                                case 'cancelled':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Anulowane';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = ucfirst($order['status']);
                            }
                            ?>
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900 mr-3 edit-status" data-order-id="<?php echo $order['id']; ?>" data-order-number="<?php echo $order['order_number']; ?>" data-status="<?php echo $order['status']; ?>">
                                Zmień status
                            </button>
                            <a href="order.php?id=<?php echo $order['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                Szczegóły
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Paginacja -->
        <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Poprzednia
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Następna
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Pokazuje <span class="font-medium"><?php echo $offset + 1; ?></span> do <span class="font-medium"><?php echo min($offset + $per_page, $total_rows); ?></span> z <span class="font-medium"><?php echo $total_rows; ?></span> wyników
                        </p>
                    </div>
                    
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Poprzednia</span>
                                <i class="ri-arrow-left-s-line"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            if ($start_page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                1
                            </a>
                            <?php if ($start_page > 2): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                            <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i == $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> text-sm font-medium">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>
                            
                            <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                            <?php endif; ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <?php echo $total_pages; ?>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Następna</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Modal zmiany statusu -->
<div id="status-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Zmień status zamówienia</h3>
        </div>
        
        <form method="post" action="">
            <div class="px-6 py-4">
                <input type="hidden" name="order_id" id="order_id" value="">
                
                <div class="mb-4">
                    <p class="text-gray-700">Zamówienie: <span id="order-number" class="font-semibold"></span></p>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status-select" name="status" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="pending">Oczekujące</option>
                        <option value="processing">W realizacji</option>
                        <option value="shipped">Wysłane</option>
                        <option value="completed">Zrealizowane</option>
                        <option value="cancelled">Anulowane</option>
                    </select>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t flex justify-end space-x-2">
                <button type="button" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-300 transition" id="status-cancel">
                    Anuluj
                </button>
                <button type="submit" name="update_status" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
                    Zapisz
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Skrypt JS do obsługi modalu zmiany statusu
$extra_js = <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusModal = document.getElementById('status-modal');
    const editStatusButtons = document.querySelectorAll('.edit-status');
    const statusCancel = document.getElementById('status-cancel');
    const orderIdInput = document.getElementById('order_id');
    const orderNumberSpan = document.getElementById('order-number');
    const statusSelect = document.getElementById('status-select');
    
    editStatusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const orderNumber = this.getAttribute('data-order-number');
            const status = this.getAttribute('data-status');
            
            orderIdInput.value = orderId;
            orderNumberSpan.textContent = orderNumber;
            statusSelect.value = status;
            
            statusModal.classList.remove('hidden');
        });
    });
    
    statusCancel.addEventListener('click', function() {
        statusModal.classList.add('hidden');
    });
    
    // Zamykanie modalu po kliknięciu poza nim
    statusModal.addEventListener('click', function(e) {
        if (e.target === statusModal) {
            statusModal.classList.add('hidden');
        }
    });
});
</script>
EOT;

include 'includes/footer.php';
?>
