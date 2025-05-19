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

$page_title = "Zarządzanie rezerwacjami serwisowymi";

// Parametry filtrowania
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$mechanic_filter = isset($_GET['mechanic']) ? (int)$_GET['mechanic'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Aktualizacja statusu rezerwacji
$message = '';
if (isset($_POST['update_status']) && isset($_POST['booking_id']) && isset($_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = sanitize($_POST['status']);
    
    $update_query = "UPDATE service_bookings SET status = '$status' WHERE id = $booking_id";
    
    if ($conn->query($update_query) === TRUE) {
        $message = 'Status rezerwacji został zaktualizowany.';
    } else {
        $message = 'Wystąpił błąd podczas aktualizacji statusu rezerwacji: ' . $conn->error;
    }
}

// Pobieranie mechaników
$mechanics_query = "SELECT * FROM mechanics WHERE status = 'active' ORDER BY name";
$mechanics = [];
$mechanics_result = $conn->query($mechanics_query);

if ($mechanics_result && $mechanics_result->num_rows > 0) {
    while ($row = $mechanics_result->fetch_assoc()) {
        $mechanics[] = $row;
    }
}

// Budowanie zapytania SQL
$query = "SELECT sb.*, 
          m.name AS mechanic_name, 
          s.name AS service_name,
          u.email AS user_email
          FROM service_bookings sb
          LEFT JOIN mechanics m ON sb.mechanic_id = m.id
          LEFT JOIN services s ON sb.service_id = s.id
          LEFT JOIN users u ON sb.user_id = u.id
          WHERE 1=1";

// Dodawanie filtrów
if (!empty($status_filter)) {
    $query .= " AND sb.status = '$status_filter'";
}

if (!empty($date_from)) {
    $query .= " AND DATE(sb.booking_date) >= '$date_from'";
}

if (!empty($date_to)) {
    $query .= " AND DATE(sb.booking_date) <= '$date_to'";
}

if ($mechanic_filter > 0) {
    $query .= " AND sb.mechanic_id = $mechanic_filter";
}

if (!empty($search)) {
    $query .= " AND (sb.id LIKE '%$search%' OR u.email LIKE '%$search%' OR sb.notes LIKE '%$search%')";
}

// Sortowanie domyślnie po dacie rezerwacji
$query .= " ORDER BY sb.booking_date DESC, sb.booking_time ASC";

// Obliczanie całkowitej liczby wyników dla paginacji
$count_query = str_replace("SELECT sb.*, 
          m.name AS mechanic_name, 
          s.name AS service_name,
          u.email AS user_email", "SELECT COUNT(*) as total", $query);
$count_query = preg_replace('/ORDER BY.*$/i', '', $count_query);

$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Ograniczenie wyników do bieżącej strony
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $offset, $per_page";

// Wykonanie zapytania
$result = $conn->query($query);
$bookings = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zarządzanie rezerwacjami serwisowymi</h1>
    </div>
    
    <?php if (!empty($message)): ?>
    <div class="bg-green-50 text-green-800 rounded-lg p-4 mb-6">
        <?php echo $message; ?>
    </div>
    <?php endif; ?>
    
    <!-- Filtry -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="" method="get" class="space-y-4 md:space-y-0 md:flex md:flex-wrap md:items-end md:gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Oczekująca</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Potwierdzona</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Zakończona</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Anulowana</option>
                </select>
            </div>
            
            <div>
                <label for="mechanic" class="block text-sm font-medium text-gray-700 mb-1">Mechanik</label>
                <select id="mechanic" name="mechanic" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="0">Wszyscy</option>
                    <?php foreach ($mechanics as $mechanic): ?>
                    <option value="<?php echo $mechanic['id']; ?>" <?php echo $mechanic_filter == $mechanic['id'] ? 'selected' : ''; ?>>
                        <?php echo $mechanic['name']; ?>
                    </option>
                    <?php endforeach; ?>
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
                <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="ID rezerwacji, email, notatki..." class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
                    Filtruj
                </button>
                <a href="service_bookings.php" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Lista rezerwacji -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($bookings)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Brak rezerwacji serwisowych spełniających kryteria wyszukiwania.</p>
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
                            Data i godzina
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Klient
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mechanik
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usługa
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
                    <?php foreach ($bookings as $booking): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $booking['id']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php 
                                $date = new DateTime($booking['booking_date']);
                                echo $date->format('d.m.Y'); 
                                ?>
                            </div>
                            <div class="text-sm text-gray-500"><?php echo $booking['booking_time']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo $booking['user_email'] ?: 'Gość'; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo $booking['mechanic_name']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo $booking['service_name']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($booking['status']) {
                                case 'pending':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Oczekująca';
                                    break;
                                case 'confirmed':
                                    $status_class = 'bg-blue-100 text-blue-800';
                                    $status_text = 'Potwierdzona';
                                    break;
                                case 'completed':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Zakończona';
                                    break;
                                case 'cancelled':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Anulowana';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = $booking['status'];
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900 mr-3" 
                                    onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                Szczegóły
                            </button>
                            <button class="text-indigo-600 hover:text-indigo-900"
                                    onclick="changeStatus(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">
                                Zmień status
                            </button>
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
                            Pokazuje <span class="font-medium"><?php echo $offset + 1; ?></span> do <span class="font-medium"><?php echo min($offset + $per_page, $total_rows); ?></span> z <span class="font-medium"><?php echo $total_rows; ?></span> rezerwacji
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Poprzednia</span>
                                <i class="ri-arrow-left-s-line"></i>
                            </a>
                            <?php else: ?>
                            <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                <span class="sr-only">Poprzednia</span>
                                <i class="ri-arrow-left-s-line"></i>
                            </span>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            if ($start_page > 1) {
                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i == $page ? 'bg-blue-50 text-blue-600 font-semibold z-10' : 'bg-white text-gray-500 hover:bg-gray-50'; ?> text-sm">
                                <?php echo $i; ?>
                            </a>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Następna</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                            <?php else: ?>
                            <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                <span class="sr-only">Następna</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </span>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal ze szczegółami rezerwacji -->
<div id="booking-details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-xl w-full p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Szczegóły rezerwacji</h2>
            <button type="button" onclick="closeBookingDetails()" class="text-gray-400 hover:text-gray-500">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div id="booking-details-content" class="space-y-4">
            <!-- Tutaj będą dynamicznie wstawiane szczegóły rezerwacji -->
            <div class="flex justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal zmiany statusu -->
<div id="status-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Zmień status rezerwacji</h2>
            <button type="button" onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-500">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form id="status-form" method="POST" action="">
            <input type="hidden" id="booking_id" name="booking_id" value="">
            <input type="hidden" name="update_status" value="1">
            
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status_select" name="status" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="pending">Oczekująca</option>
                    <option value="confirmed">Potwierdzona</option>
                    <option value="completed">Zakończona</option>
                    <option value="cancelled">Anulowana</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    Anuluj
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Zapisz zmiany
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Funkcja do pokazywania szczegółów rezerwacji
function viewBookingDetails(bookingId) {
    const modal = document.getElementById('booking-details-modal');
    const content = document.getElementById('booking-details-content');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Ładowanie szczegółów z AJAX
    content.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>';
    
    // Tutaj należy zaimplementować ładowanie szczegółów przez AJAX
    // W rzeczywistej implementacji
    setTimeout(() => {
        fetch(`../admin/ajax/get_booking_details.php?id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = data.html;
                } else {
                    content.innerHTML = `<div class="text-red-600 text-center">${data.error || 'Wystąpił błąd podczas ładowania szczegółów'}</div>`;
                }
            })
            .catch(error => {
                content.innerHTML = '<div class="text-red-600 text-center">Wystąpił błąd podczas ładowania szczegółów</div>';
                console.error('Error:', error);
            });
    }, 500);
}

// Funkcja do zamykania modalu szczegółów
function closeBookingDetails() {
    const modal = document.getElementById('booking-details-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Funkcja do otwierania modalu zmiany statusu
function changeStatus(bookingId, currentStatus) {
    const modal = document.getElementById('status-modal');
    const bookingIdInput = document.getElementById('booking_id');
    const statusSelect = document.getElementById('status_select');
    
    bookingIdInput.value = bookingId;
    statusSelect.value = currentStatus;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Funkcja do zamykania modalu zmiany statusu
function closeStatusModal() {
    const modal = document.getElementById('status-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Inicjalizacja dla mobilnych filtrów
document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.getElementById('filter-toggle');
    const filtersPanel = document.getElementById('filters-panel');
    
    if (filterToggle && filtersPanel) {
        filterToggle.addEventListener('click', function() {
            if (filtersPanel.classList.contains('hidden')) {
                filtersPanel.classList.remove('hidden');
                filtersPanel.classList.add('block');
            } else {
                filtersPanel.classList.add('hidden');
                filtersPanel.classList.remove('block');
            }
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>
