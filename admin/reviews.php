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

// Sprawdzenie istnienia tabeli
$check_table_query = "SHOW TABLES LIKE 'product_reviews'";
$table_exists = $conn->query($check_table_query)->num_rows > 0;

if (!$table_exists) {
    // Wykonanie skryptu tworzącego tabelę
    $sql_path = $base_path . '/database/add_reviews_table.sql';
    if (file_exists($sql_path)) {
        $sql = file_get_contents($sql_path);
        $conn->multi_query($sql);
        
        // Oczekiwanie na zakończenie wszystkich zapytań
        while ($conn->more_results() && $conn->next_result()) {
            // Pomiń wyniki, jeśli istnieją
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
        
        $success_msg = "Tabela recenzji została utworzona pomyślnie.";
    } else {
        $error_msg = "Nie można znaleźć pliku SQL do tworzenia tabeli recenzji.";
    }
}

$page_title = "Zarządzanie Recenzjami";

// Obsługa zatwierdzania/odrzucania recenzji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_review'])) {
        $review_id = (int)$_POST['review_id'];
        $update_query = "UPDATE product_reviews SET status = 'approved' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $review_id);
        
        if ($stmt->execute()) {
            $success_msg = "Recenzja została zatwierdzona.";
        } else {
            $error_msg = "Błąd podczas zatwierdzania recenzji: " . $conn->error;
        }
    } else if (isset($_POST['reject_review'])) {
        $review_id = (int)$_POST['review_id'];
        $update_query = "UPDATE product_reviews SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $review_id);
        
        if ($stmt->execute()) {
            $success_msg = "Recenzja została odrzucona.";
        } else {
            $error_msg = "Błąd podczas odrzucania recenzji: " . $conn->error;
        }
    } else if (isset($_POST['delete_review'])) {
        $review_id = (int)$_POST['review_id'];
        $delete_query = "DELETE FROM product_reviews WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $review_id);
        
        if ($stmt->execute()) {
            $success_msg = "Recenzja została usunięta.";
        } else {
            $error_msg = "Błąd podczas usuwania recenzji: " . $conn->error;
        }
    }
}

// Filtrowanie recenzji
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$product_filter = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Budowanie zapytania z filtrami
$query = "
    SELECT pr.*, p.name as product_name, u.first_name, u.last_name, u.email
    FROM product_reviews pr
    JOIN products p ON pr.product_id = p.id
    LEFT JOIN users u ON pr.user_id = u.id
    WHERE 1=1
";

$params = [];
$param_types = "";

if ($status_filter && in_array($status_filter, ['pending', 'approved', 'rejected'])) {
    $query .= " AND pr.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($product_filter > 0) {
    $query .= " AND pr.product_id = ?";
    $params[] = $product_filter;
    $param_types .= "i";
}

$query .= " ORDER BY pr.created_at DESC";

// Przygotowanie i wykonanie zapytania
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

// Pobranie listy produktów do filtra
$products_query = "SELECT id, name FROM products ORDER BY name";
$products_result = $conn->query($products_query);
$products = [];
if ($products_result) {
    while ($row = $products_result->fetch_assoc()) {
        $products[$row['id']] = $row['name'];
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
                <h1 class="text-2xl font-bold text-gray-800">Zarządzanie Recenzjami</h1>
                <p class="text-gray-600">Przeglądaj i moderuj recenzje produktów</p>
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
        
        <!-- Filtry -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Filtry</h2>
            </div>
            <div class="p-4">
                <form action="" method="GET" class="flex flex-wrap gap-4">
                    <div class="w-full md:w-auto">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Wszystkie statusy</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Oczekujące</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Zatwierdzone</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Odrzucone</option>
                        </select>
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="product_id" class="block text-sm font-medium text-gray-700 mb-1">Produkt</label>
                        <select name="product_id" id="product_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Wszystkie produkty</option>
                            <?php foreach ($products as $id => $name): ?>
                            <option value="<?php echo $id; ?>" <?php echo $product_filter === $id ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-full md:w-auto flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                            Filtruj
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Lista recenzji -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Recenzje produktów</h2>
            </div>
            
            <?php if (empty($reviews)): ?>
            <div class="p-6">
                <p class="text-gray-500 text-center">Brak recenzji spełniających kryteria.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produkt</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Użytkownik</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ocena</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tytuł</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($reviews as $review): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900">#<?php echo $review['id']; ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900"><?php echo htmlspecialchars($review['product_name']); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($review['user_id']): ?>
                                <span class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                    <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($review['email']); ?>)</span>
                                </span>
                                <?php else: ?>
                                <span class="text-sm text-gray-500">Gość</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-4 h-4 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    <?php endfor; ?>
                                    <span class="ml-1 text-sm text-gray-500">(<?php echo $review['rating']; ?>/5)</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($review['title'] ?? ''); ?></p>
                                    <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars(substr($review['content'], 0, 100) . (strlen($review['content']) > 100 ? '...' : '')); ?></p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($review['status'] === 'pending'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Oczekująca
                                </span>
                                <?php elseif ($review['status'] === 'approved'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Zatwierdzona
                                </span>
                                <?php elseif ($review['status'] === 'rejected'): ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Odrzucona
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button type="button" onclick="showReviewDetails(<?php echo $review['id']; ?>, '<?php echo addslashes(htmlspecialchars($review['title'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($review['content'])); ?>', '<?php echo addslashes(htmlspecialchars($review['product_name'])); ?>', '<?php echo $review['rating']; ?>')" class="text-blue-600 hover:text-blue-900">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    
                                    <?php if ($review['status'] === 'pending'): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Czy na pewno chcesz zatwierdzić tę recenzję?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="approve_review" class="text-green-600 hover:text-green-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Czy na pewno chcesz odrzucić tę recenzję?');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="reject_review" class="text-red-600 hover:text-red-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Czy na pewno chcesz usunąć tę recenzję? Tej operacji nie można cofnąć.');">
                                        <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                        <button type="submit" name="delete_review" class="text-red-600 hover:text-red-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
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

<!-- Modal szczegółów recenzji -->
<div id="reviewDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="bg-white rounded-lg w-full max-w-2xl mx-4">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900">Szczegóły recenzji</h3>
            <button type="button" onclick="hideReviewDetailsModal()" class="text-gray-400 hover:text-gray-500">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-4">
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-500">Produkt</h4>
                <p id="reviewProductName" class="text-base text-gray-900"></p>
            </div>
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-500">Ocena</h4>
                <div id="reviewRating" class="flex items-center"></div>
            </div>
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-500">Tytuł</h4>
                <p id="reviewTitle" class="text-base text-gray-900"></p>
            </div>
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-500">Treść</h4>
                <p id="reviewContent" class="text-base text-gray-900 whitespace-pre-line"></p>
            </div>
        </div>
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button type="button" onclick="hideReviewDetailsModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-md">
                Zamknij
            </button>
        </div>
    </div>
</div>

<script>
    function showReviewDetails(id, title, content, productName, rating) {
        document.getElementById('reviewTitle').textContent = title;
        document.getElementById('reviewContent').textContent = content;
        document.getElementById('reviewProductName').textContent = productName;
        
        // Generowanie gwiazdek dla oceny
        const ratingContainer = document.getElementById('reviewRating');
        ratingContainer.innerHTML = '';
        
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('svg');
            star.classList.add('w-5', 'h-5');
            star.setAttribute('fill', 'currentColor');
            star.setAttribute('viewBox', '0 0 20 20');
            star.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            
            if (i <= rating) {
                star.classList.add('text-yellow-400');
            } else {
                star.classList.add('text-gray-300');
            }
            
            star.innerHTML = '<path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>';
            
            ratingContainer.appendChild(star);
        }
        
        // Dodajemy numeryczną wartość oceny
        const ratingText = document.createElement('span');
        ratingText.classList.add('ml-1', 'text-sm', 'text-gray-500');
        ratingText.textContent = `(${rating}/5)`;
        ratingContainer.appendChild(ratingText);
        
        document.getElementById('reviewDetailsModal').classList.remove('hidden');
    }
    
    function hideReviewDetailsModal() {
        document.getElementById('reviewDetailsModal').classList.add('hidden');
    }
</script>

<?php include 'includes/footer.php'; ?>
