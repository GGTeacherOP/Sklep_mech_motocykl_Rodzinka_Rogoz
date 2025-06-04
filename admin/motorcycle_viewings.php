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

$page_title = "Zarządzanie rezerwacjami oględzin";

// Parametry filtrowania
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;

// Aktualizacja statusu rezerwacji
$message = '';
$error = '';

if (isset($_POST['update_status']) && isset($_POST['viewing_id']) && isset($_POST['status'])) {
    $viewing_id = (int)$_POST['viewing_id'];
    $status = sanitize($_POST['status']);
    
    $update_query = "UPDATE motorcycle_viewings SET status = '$status' WHERE id = $viewing_id";
    
    if ($conn->query($update_query) === TRUE) {
        $message = 'Status rezerwacji oględzin został zaktualizowany.';
        
        // Jeśli status to "cancelled" (anulowano), sprawdź czy należy zaktualizować status motocykla
        if ($status == 'cancelled') {
            $check_query = "SELECT motorcycle_id FROM motorcycle_viewings WHERE id = $viewing_id";
            $check_result = $conn->query($check_query);
            
            if ($check_result && $check_result->num_rows > 0) {
                $motorcycle_id = $check_result->fetch_assoc()['motorcycle_id'];
                
                // Sprawdź, czy istnieją inne aktywne rezerwacje dla tego motocykla
                $active_query = "SELECT COUNT(*) as count FROM motorcycle_viewings 
                                WHERE motorcycle_id = $motorcycle_id 
                                AND status IN ('pending', 'confirmed') 
                                AND id != $viewing_id";
                
                $active_result = $conn->query($active_query);
                $active_count = $active_result->fetch_assoc()['count'];
                
                // Jeśli nie ma innych aktywnych rezerwacji, zmień status motocykla na "available"
                if ($active_count == 0) {
                    $update_motorcycle_query = "UPDATE used_motorcycles SET status = 'available' WHERE id = $motorcycle_id";
                    $conn->query($update_motorcycle_query);
                }
            }
        }
        
        // Jeśli status to "confirmed" (potwierdzono), zaktualizuj status motocykla na "reserved"
        if ($status == 'confirmed') {
            $check_query = "SELECT motorcycle_id FROM motorcycle_viewings WHERE id = $viewing_id";
            $check_result = $conn->query($check_query);
            
            if ($check_result && $check_result->num_rows > 0) {
                $motorcycle_id = $check_result->fetch_assoc()['motorcycle_id'];
                $update_motorcycle_query = "UPDATE used_motorcycles SET status = 'reserved' WHERE id = $motorcycle_id";
                $conn->query($update_motorcycle_query);
            }
        }
    } else {
        $error = 'Wystąpił błąd podczas aktualizacji statusu rezerwacji: ' . $conn->error;
    }
}

// Budowanie zapytania SQL
$query = "SELECT mv.*, 
          um.title AS motorcycle_title, um.brand, um.model, um.year,
          u.email AS user_email
          FROM motorcycle_viewings mv
          LEFT JOIN used_motorcycles um ON mv.motorcycle_id = um.id
          LEFT JOIN users u ON mv.user_id = u.id
          WHERE 1=1";

// Dodawanie filtrów
if (!empty($status_filter)) {
    $query .= " AND mv.status = '$status_filter'";
}

if (!empty($date_from)) {
    $query .= " AND DATE(mv.date) >= '$date_from'";
}

if (!empty($date_to)) {
    $query .= " AND DATE(mv.date) <= '$date_to'";
}

if (!empty($search)) {
    $query .= " AND (mv.first_name LIKE '%$search%' OR mv.last_name LIKE '%$search%' OR 
                mv.email LIKE '%$search%' OR mv.phone LIKE '%$search%' OR 
                um.title LIKE '%$search%' OR um.brand LIKE '%$search%' OR um.model LIKE '%$search%')";
}

// Sortowanie domyślnie po dacie
$query .= " ORDER BY mv.date DESC, mv.time ASC";

// Obliczanie całkowitej liczby wyników dla paginacji
$count_query = str_replace("SELECT mv.*, 
          um.title AS motorcycle_title, um.brand, um.model, um.year,
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
$viewings = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $viewings[] = $row;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zarządzanie rezerwacjami oględzin</h1>
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
    
    <!-- Filtry -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <form action="" method="get" class="space-y-4 md:space-y-0 md:flex md:flex-wrap md:items-end md:gap-4">
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Oczekujące</option>
                    <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Potwierdzone</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Zakończone</option>
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
                <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="Imię, nazwisko, email, telefon, motocykl..." class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
                    Filtruj
                </button>
                <a href="motorcycle_viewings.php" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Lista rezerwacji -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($viewings)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Brak rezerwacji oględzin spełniających kryteria wyszukiwania.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data i godzina
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Klient
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Motocykl
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
                    <?php foreach ($viewings as $viewing): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php 
                                $date = new DateTime($viewing['date']);
                                echo $date->format('d.m.Y'); 
                                ?>
                            </div>
                            <div class="text-sm text-gray-500"><?php echo $viewing['time']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo $viewing['first_name'] . ' ' . $viewing['last_name']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $viewing['email']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $viewing['phone']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">
                                <a href="../motorcycle.php?id=<?php echo $viewing['motorcycle_id']; ?>" target="_blank" class="hover:text-blue-600">
                                    <?php echo $viewing['motorcycle_title']; ?>
                                </a>
                            </div>
                            <div class="text-sm text-gray-500"><?php echo $viewing['brand'] . ' ' . $viewing['model'] . ' (' . $viewing['year'] . ')'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($viewing['status']) {
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
                                    $status_text = $viewing['status'];
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900 mr-3" 
                                    onclick="viewDetails(<?php echo $viewing['id']; ?>)">
                                Szczegóły
                            </button>
                            <button class="text-indigo-600 hover:text-indigo-900"
                                    onclick="changeStatus(<?php echo $viewing['id']; ?>, '<?php echo $viewing['status']; ?>')">
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

<!-- Modal szczegółów rezerwacji -->
<div id="details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-xl w-full p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Szczegóły rezerwacji oględzin</h2>
            <button type="button" onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-500">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div id="details-content" class="space-y-4">
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
            <input type="hidden" id="viewing_id" name="viewing_id" value="">
            <input type="hidden" name="update_status" value="1">
            
            <div class="mb-4">
                <label for="status_select" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
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
function viewDetails(viewingId) {
    const modal = document.getElementById('details-modal');
    const content = document.getElementById('details-content');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Ładowanie szczegółów z AJAX
    content.innerHTML = '<div class="flex justify-center"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>';
    
    // Tutaj należy zaimplementować ładowanie szczegółów przez AJAX
    // W rzeczywistej implementacji
    setTimeout(() => {
        fetch(`../admin/ajax/get_viewing_details.php?id=${viewingId}`)
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
function closeDetailsModal() {
    const modal = document.getElementById('details-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Funkcja do otwierania modalu zmiany statusu
function changeStatus(viewingId, currentStatus) {
    const modal = document.getElementById('status-modal');
    const viewingIdInput = document.getElementById('viewing_id');
    const statusSelect = document.getElementById('status_select');
    
    viewingIdInput.value = viewingId;
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
</script>

<?php
include 'includes/footer.php';
?>
