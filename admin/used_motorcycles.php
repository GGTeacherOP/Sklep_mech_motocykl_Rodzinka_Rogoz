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

$page_title = "Zarządzanie motocyklami używanymi";

// Parametry filtrowania
$brand_filter = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$condition_filter = isset($_GET['condition']) ? sanitize($_GET['condition']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Aktualizacja statusu motocykla
$message = '';
$error = '';

if (isset($_POST['update_status']) && isset($_POST['motorcycle_id']) && isset($_POST['status'])) {
    $motorcycle_id = (int)$_POST['motorcycle_id'];
    $status = sanitize($_POST['status']);
    
    $update_query = "UPDATE used_motorcycles SET status = '$status' WHERE id = $motorcycle_id";
    
    if ($conn->query($update_query) === TRUE) {
        $message = 'Status motocykla został zaktualizowany.';
    } else {
        $error = 'Wystąpił błąd podczas aktualizacji statusu motocykla: ' . $conn->error;
    }
}

// Pobieranie marek dla filtrów
$brands_query = "SELECT DISTINCT brand FROM used_motorcycles ORDER BY brand";
$brands = [];
$brands_result = $conn->query($brands_query);

if ($brands_result && $brands_result->num_rows > 0) {
    while ($row = $brands_result->fetch_assoc()) {
        $brands[] = $row['brand'];
    }
}

// Budowanie zapytania SQL
$query = "SELECT m.*, 
         (SELECT image_path FROM motorcycle_images mi WHERE mi.motorcycle_id = m.id AND mi.is_main = 1 LIMIT 1) as image_path
         FROM used_motorcycles m
         WHERE 1=1";

// Dodawanie filtrów
if (!empty($brand_filter)) {
    $query .= " AND m.brand = '$brand_filter'";
}

if (!empty($status_filter)) {
    $query .= " AND m.status = '$status_filter'";
}

if (!empty($condition_filter)) {
    $query .= " AND m.condition = '$condition_filter'";
}

if (!empty($search)) {
    $query .= " AND (m.title LIKE '%$search%' OR m.description LIKE '%$search%' OR m.vin LIKE '%$search%')";
}

// Sortowanie domyślnie po ID (najnowsze najpierw)
$query .= " ORDER BY m.id DESC";

// Obliczanie całkowitej liczby wyników dla paginacji
$count_query = str_replace("SELECT m.*, 
         (SELECT image_path FROM motorcycle_images mi WHERE mi.motorcycle_id = m.id AND mi.is_main = 1 LIMIT 1) as image_path", "SELECT COUNT(*) as total", $query);
$count_query = preg_replace('/ORDER BY.*$/i', '', $count_query);

$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Ograniczenie wyników do bieżącej strony
$offset = ($page - 1) * $per_page;
$query .= " LIMIT $offset, $per_page";

// Wykonanie zapytania
$result = $conn->query($query);
$motorcycles = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $motorcycles[] = $row;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zarządzanie motocyklami używanymi</h1>
        <a href="motorcycle_form.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
            Dodaj motocykl
        </a>
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
                <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Marka</label>
                <select id="brand" name="brand" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Wszystkie</option>
                    <?php foreach ($brands as $brand): ?>
                    <option value="<?php echo $brand; ?>" <?php echo $brand_filter === $brand ? 'selected' : ''; ?>>
                        <?php echo $brand; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Dostępny</option>
                    <option value="reserved" <?php echo $status_filter === 'reserved' ? 'selected' : ''; ?>>Zarezerwowany</option>
                    <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sprzedany</option>
                    <option value="hidden" <?php echo $status_filter === 'hidden' ? 'selected' : ''; ?>>Ukryty</option>
                </select>
            </div>
            
            <div>
                <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">Stan</label>
                <select id="condition" name="condition" class="border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Wszystkie</option>
                    <option value="excellent" <?php echo $condition_filter === 'excellent' ? 'selected' : ''; ?>>Doskonały</option>
                    <option value="very_good" <?php echo $condition_filter === 'very_good' ? 'selected' : ''; ?>>Bardzo dobry</option>
                    <option value="good" <?php echo $condition_filter === 'good' ? 'selected' : ''; ?>>Dobry</option>
                    <option value="average" <?php echo $condition_filter === 'average' ? 'selected' : ''; ?>>Średni</option>
                </select>
            </div>
            
            <div class="flex-grow">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Szukaj</label>
                <input type="text" id="search" name="search" value="<?php echo $search; ?>" placeholder="Nazwa, opis, VIN..." class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex space-x-2">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
                    Filtruj
                </button>
                <a href="used_motorcycles.php" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-300 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>
    
    <!-- Lista motocykli -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <?php if (empty($motorcycles)): ?>
        <div class="p-6 text-center">
            <p class="text-gray-500">Brak motocykli używanych spełniających kryteria wyszukiwania.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Zdjęcie
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Motocykl
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Szczegóły
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cena
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stan
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
                    <?php foreach ($motorcycles as $motorcycle): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if (!empty($motorcycle['image_path'])): ?>
                            <img src="<?php echo '../' . $motorcycle['image_path']; ?>" alt="<?php echo $motorcycle['title']; ?>" class="h-16 w-24 object-cover rounded">
                            <?php else: ?>
                            <div class="h-16 w-24 bg-gray-200 flex items-center justify-center rounded">
                                <i class="ri-image-line text-gray-400 text-xl"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900"><?php echo $motorcycle['title']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $motorcycle['brand']; ?>, <?php echo $motorcycle['year']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo $motorcycle['engine_capacity']; ?> cm³</div>
                            <div class="text-sm text-gray-500"><?php echo number_format($motorcycle['mileage'], 0, ',', ' '); ?> km</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo number_format($motorcycle['price'], 0, ',', ' '); ?> zł</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $condition_text = '';
                            
                            switch ($motorcycle['condition']) {
                                case 'excellent':
                                    $condition_text = 'Doskonały';
                                    break;
                                case 'very_good':
                                    $condition_text = 'Bardzo dobry';
                                    break;
                                case 'good':
                                    $condition_text = 'Dobry';
                                    break;
                                case 'average':
                                    $condition_text = 'Średni';
                                    break;
                                default:
                                    $condition_text = $motorcycle['condition'];
                            }
                            ?>
                            <div class="text-sm text-gray-900"><?php echo $condition_text; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $status_class = '';
                            $status_text = '';
                            
                            switch ($motorcycle['status']) {
                                case 'available':
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'Dostępny';
                                    break;
                                case 'reserved':
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'Zarezerwowany';
                                    break;
                                case 'sold':
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'Sprzedany';
                                    break;
                                case 'hidden':
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = 'Ukryty';
                                    break;
                                default:
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = $motorcycle['status'];
                            }
                            ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="motorcycle_form.php?id=<?php echo $motorcycle['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                Edytuj
                            </a>
                            <button class="text-indigo-600 hover:text-indigo-900 mr-3" 
                                    onclick="changeStatus(<?php echo $motorcycle['id']; ?>, '<?php echo $motorcycle['status']; ?>')">
                                Status
                            </button>
                            <a href="../motorcycle.php?id=<?php echo $motorcycle['id']; ?>" target="_blank" class="text-gray-600 hover:text-gray-900">
                                Podgląd
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
                            Pokazuje <span class="font-medium"><?php echo $offset + 1; ?></span> do <span class="font-medium"><?php echo min($offset + $per_page, $total_rows); ?></span> z <span class="font-medium"><?php echo $total_rows; ?></span> motocykli
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

<!-- Modal zmiany statusu -->
<div id="status-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-6 mx-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Zmień status motocykla</h2>
            <button type="button" onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-500">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <form id="status-form" method="POST" action="">
            <input type="hidden" id="motorcycle_id" name="motorcycle_id" value="">
            <input type="hidden" name="update_status" value="1">
            
            <div class="mb-4">
                <label for="status_select" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status_select" name="status" class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value="available">Dostępny</option>
                    <option value="reserved">Zarezerwowany</option>
                    <option value="sold">Sprzedany</option>
                    <option value="hidden">Ukryty</option>
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
// Funkcja do otwierania modalu zmiany statusu
function changeStatus(motorcycleId, currentStatus) {
    const modal = document.getElementById('status-modal');
    const motorcycleIdInput = document.getElementById('motorcycle_id');
    const statusSelect = document.getElementById('status_select');
    
    motorcycleIdInput.value = motorcycleId;
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
