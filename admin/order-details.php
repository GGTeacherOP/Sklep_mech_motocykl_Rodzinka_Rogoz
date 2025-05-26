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

// Sprawdzenie, czy przekazano ID zamówienia
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: orders.php");
    exit;
}

$order_id = (int)$_GET['id'];
$page_title = "Szczegóły Zamówienia #$order_id";

// Pobranie danych zamówienia
$order_query = "SELECT o.*, u.email, u.first_name, u.last_name, u.phone
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

// Sprawdzenie, czy zamówienie istnieje
if (!$result || $result->num_rows === 0) {
    header("Location: orders.php");
    exit;
}

$order = $result->fetch_assoc();

// Pobranie pozycji zamówienia
$items_query = "SELECT oi.*, p.name, p.sku
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];

if ($items_result && $items_result->num_rows > 0) {
    while ($row = $items_result->fetch_assoc()) {
        $order_items[] = $row;
    }
}

// Obsługa zmiany statusu zamówienia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
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
                $success_msg = "Status zamówienia został zaktualizowany z '$current_status' na '$new_status'.";
                $order['status'] = $new_status;
            } else {
                $error_msg = "Błąd podczas aktualizacji statusu: " . $conn->error;
            }
        } else {
            $error_msg = "Nie można znaleźć zamówienia o ID: $order_id";
        }
    }
}

// Usunięto funkcjonalność komentarzy do zamówień

include 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Główna zawartość -->
    <div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Szczegóły Zamówienia #<?php echo $order_id; ?></h1>
                <p class="text-gray-600">
                    Data zamówienia: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?>
                </p>
            </div>
            <div class="mt-3 md:mt-0">
                <a href="orders.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 px-4 rounded-md inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Wróć do listy
                </a>
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
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Status zamówienia -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Status zamówienia</h2>
                
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
                
                <div class="mb-4">
                    <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="mb-4">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Zmień status</label>
                        <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Oczekujące</option>
                            <option value="processing" <?php echo $status == 'processing' ? 'selected' : ''; ?>>W trakcie realizacji</option>
                            <option value="shipped" <?php echo $status == 'shipped' ? 'selected' : ''; ?>>Wysłane</option>
                            <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Zakończone</option>
                            <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Anulowane</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md">
                        Zapisz status
                    </button>
                </form>
            </div>
            
            <!-- Dane klienta -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Dane klienta</h2>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Nazwa</p>
                        <p class="font-medium">
                            <?php
                            if (!empty($order['first_name']) && !empty($order['last_name'])) {
                                echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                            } else {
                                echo 'Brak danych';
                            }
                            ?>
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium">
                            <?php echo htmlspecialchars($order['email'] ?? 'Brak danych'); ?>
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Telefon</p>
                        <p class="font-medium">
                            <?php echo htmlspecialchars($order['phone'] ?? 'Brak danych'); ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($order['user_id'])): ?>
                    <div class="pt-3">
                        <a href="users.php?id=<?php echo $order['user_id']; ?>" class="text-blue-600 hover:text-blue-800">
                            Zobacz profil klienta
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informacje o płatności -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Informacje o płatności</h2>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Metoda płatności</p>
                        <p class="font-medium">
                            <?php
                            $payment_method = $order['payment_method'] ?? '';
                            $payment_text = '';
                            
                            switch ($payment_method) {
                                case 'online':
                                    $payment_text = 'Płatność online';
                                    break;
                                case 'card':
                                    $payment_text = 'Karta płatnicza';
                                    break;
                                case 'cod':
                                    $payment_text = 'Płatność przy odbiorze';
                                    break;
                                case 'transfer':
                                    $payment_text = 'Przelew tradycyjny';
                                    break;
                                default:
                                    $payment_text = ucfirst($payment_method);
                            }
                            
                            echo $payment_text ?: 'Brak danych';
                            ?>
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Status płatności</p>
                        <p class="font-medium">
                            <?php
                            $payment_status = $order['payment_status'] ?? '';
                            $payment_status_text = '';
                            
                            switch ($payment_status) {
                                case 'paid':
                                    $payment_status_text = 'Opłacone';
                                    break;
                                case 'pending':
                                    $payment_status_text = 'Oczekujące na płatność';
                                    break;
                                case 'failed':
                                    $payment_status_text = 'Płatność nie powiodła się';
                                    break;
                                default:
                                    $payment_status_text = ucfirst($payment_status);
                            }
                            
                            echo $payment_status_text ?: 'Brak danych';
                            ?>
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Suma</p>
                        <p class="font-medium text-lg">
                            <?php echo number_format($order['total'], 2, ',', ' '); ?> zł
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Adres dostawy -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Adres dostawy</h2>
                
                <div class="space-y-1">
                    <?php if (!empty($order['shipping_address'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                    <?php else: ?>
                        <p class="text-gray-500">Brak danych adresowych</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Metoda dostawy -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Metoda dostawy</h2>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Sposób dostawy</p>
                        <p class="font-medium">
                            <?php echo htmlspecialchars($order['shipping_method'] ?? 'Brak danych'); ?>
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Koszt dostawy</p>
                        <p class="font-medium">
                            <?php 
                            if (isset($order['shipping_cost'])) {
                                echo number_format($order['shipping_cost'], 2, ',', ' ') . ' zł';
                            } else {
                                echo 'Brak danych';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Dodatkowe informacje -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold mb-4">Dodatkowe informacje</h2>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Uwagi do zamówienia</p>
                        <p class="font-medium">
                            <?php
                            if (!empty($order['notes'])) {
                                echo nl2br(htmlspecialchars($order['notes']));
                            } else {
                                echo 'Brak uwag';
                            }
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Produkty zamówienia -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Produkty w zamówieniu</h2>
            </div>
            
            <?php if (empty($order_items)): ?>
            <div class="p-6 text-center">
                <p class="text-gray-500">Brak produktów w zamówieniu.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produkt</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cena</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ilość</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suma</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($order_items as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-200 rounded-md flex items-center justify-center">
                                        <i class="ri-shopping-bag-line text-gray-500"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($item['sku'] ?? 'Brak SKU'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($item['price'], 2, ',', ' '); ?> zł
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $item['quantity']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> zł
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-sm font-medium">Wartość produktów:</td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <?php
                                $subtotal = 0;
                                foreach ($order_items as $item) {
                                    $subtotal += $item['price'] * $item['quantity'];
                                }
                                echo number_format($subtotal, 2, ',', ' ');
                                ?> zł
                            </td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-sm font-medium">Koszt dostawy:</td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <?php echo number_format($order['shipping_cost'] ?? 0, 2, ',', ' '); ?> zł
                            </td>
                        </tr>
                        <?php if (!empty($order['discount'])): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-sm font-medium">Rabat:</td>
                            <td class="px-6 py-4 text-sm font-medium">
                                -<?php echo number_format($order['discount'], 2, ',', ' '); ?> zł
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-sm font-bold">Suma:</td>
                            <td class="px-6 py-4 text-sm font-bold">
                                <?php 
                                // Obliczanie sumy na podstawie produktów i kosztów dostawy
                                $total = $subtotal + ($order['shipping_cost'] ?? 0) - ($order['discount'] ?? 0);
                                
                                // Jeśli wartość z bazy danych jest większa niż 0, używamy jej (ale wyświetlamy też obliczoną wartość)
                                if ($order['total'] > 0 && $order['total'] != $total) {
                                    echo number_format($order['total'], 2, ',', ' ') . ' zł';
                                    echo ' <span class="text-xs text-gray-500">(obliczona: ' . number_format($total, 2, ',', ' ') . ' zł)</span>';
                                } else {
                                    // Aktualizujemy wartość w tablicy order, aby była zgodna z obliczeniami
                                    $order['total'] = $total;
                                    echo number_format($total, 2, ',', ' ') . ' zł';
                                }
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
