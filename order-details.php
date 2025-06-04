<?php
$page_title = "Szczegóły zamówienia | MotoShop";
require_once 'includes/config.php';

// Sprawdzanie czy użytkownik jest zalogowany
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];

// Sprawdzanie czy ID zamówienia zostało przekazane
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: account.php?tab=orders");
    exit;
}

$order_id = (int)$_GET['id'];

// Pobieranie danych zamówienia i sprawdzanie, czy należy do zalogowanego użytkownika
$order_query = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$order_result = $conn->query($order_query);

if (!$order_result || $order_result->num_rows === 0) {
    header("Location: account.php?tab=orders");
    exit;
}

$order = $order_result->fetch_assoc();

// Pobieranie pozycji zamówienia
$items_query = "SELECT oi.*, p.name, p.slug, pi.image_path 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_query);

$order_items = [];
if ($items_result && $items_result->num_rows > 0) {
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
}

// Ustawianie tekstów metod dostawy i płatności
$shipping_methods = [
    'courier' => 'Kurier',
    'inpost' => 'InPost Paczkomat',
    'pickup' => 'Odbiór osobisty'
];

$payment_methods = [
    'online' => 'Płatność online (Przelewy24)',
    'card' => 'Karta płatnicza',
    'cash' => 'Płatność przy odbiorze',
    'transfer' => 'Przelew tradycyjny'
];

$shipping_method_text = $shipping_methods[$order['shipping_method']] ?? $order['shipping_method'];
$payment_method_text = $payment_methods[$order['payment_method']] ?? $order['payment_method'];

// Określanie koloru statusu zamówienia
$status_class = '';
$status_text = '';

switch ($order['status']) {
    case 'pending':
        $status_class = 'bg-yellow-100 text-yellow-800';
        $status_text = 'Oczekujące';
        break;
    case 'processing':
        $status_class = 'bg-blue-100 text-blue-800';
        $status_text = 'W trakcie realizacji';
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

include 'includes/header.php';
?>

<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-3">Szczegóły zamówienia</h1>
                <nav class="flex">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="index.php" class="text-gray-700 hover:text-primary">
                                Strona główna
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <a href="account.php" class="text-gray-700 hover:text-primary">
                                    Moje konto
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <a href="account.php?tab=orders" class="text-gray-700 hover:text-primary">
                                    Zamówienia
                                </a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <span class="text-primary font-medium"><?php echo $order['order_number']; ?></span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h2 class="text-xl font-semibold mb-1">Zamówienie #<?php echo $order['order_number']; ?></h2>
                            <p class="text-gray-600">Złożone <?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></p>
                        </div>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                            <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
                            <a href="#" class="text-red-600 hover:text-red-800 cancel-order-button" data-order-id="<?php echo $order_id; ?>">
                                Anuluj zamówienie
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Pozycje zamówienia -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Produkt
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Cena
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ilość
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Razem
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($order_items as $item): 
                                $item_total = $item['price'] * $item['quantity'];
                                $image = $item['image_path'] ?? 'assets/images/placeholder.jpg';
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-14 w-14">
                                            <img class="h-14 w-14 rounded object-cover" src="<?php echo $image; ?>" alt="<?php echo $item['name']; ?>">
                                        </div>
                                        <div class="ml-4">
                                            <a href="product.php?slug=<?php echo $item['slug']; ?>" class="text-sm font-medium text-gray-900 hover:text-primary">
                                                <?php echo $item['name']; ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($item['price'], 2, ',', ' '); ?> zł</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $item['quantity']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-primary"><?php echo number_format($item_total, 2, ',', ' '); ?> zł</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Podsumowanie zamówienia -->
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <div class="flex flex-col md:flex-row justify-between gap-8">
                        <div class="md:w-7/12">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="font-medium mb-3">Adres dostawy</h3>
                                    <div class="bg-white p-4 rounded-lg">
                                        <p class="mb-1"><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></p>
                                        <p class="mb-1"><?php echo $order['address']; ?></p>
                                        <p class="mb-1"><?php echo $order['postal_code'] . ' ' . $order['city']; ?></p>
                                        <p class="mb-1"><?php echo $order['phone']; ?></p>
                                        <p><?php echo $order['email']; ?></p>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="font-medium mb-3">Informacje o zamówieniu</h3>
                                    <div class="bg-white p-4 rounded-lg">
                                        <div class="mb-2">
                                            <span class="text-gray-600 block">Metoda płatności:</span>
                                            <span class="font-medium"><?php echo $payment_method_text; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-600 block">Metoda dostawy:</span>
                                            <span class="font-medium"><?php echo $shipping_method_text; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($order['payment_method'] === 'transfer'): ?>
                            <div class="mt-6">
                                <h3 class="font-medium mb-3">Dane do przelewu</h3>
                                <div class="bg-white p-4 rounded-lg">
                                    <p class="mb-1"><strong>Nazwa odbiorcy:</strong> MotoShop Sp. z o.o.</p>
                                    <p class="mb-1"><strong>Nr rachunku:</strong> 12 3456 7890 1234 5678 9012 3456</p>
                                    <p class="mb-1"><strong>Tytuł przelewu:</strong> Zamówienie <?php echo $order['order_number']; ?></p>
                                    <p><strong>Kwota:</strong> <?php echo number_format($order['total'], 2, ',', ' '); ?> zł</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="md:w-5/12">
                            <h3 class="font-medium mb-3">Podsumowanie</h3>
                            <div class="bg-white p-4 rounded-lg">
                                <div class="space-y-2 mb-4">
                                    <div class="flex justify-between border-b pb-2">
                                        <span class="text-gray-600">Wartość produktów:</span>
                                        <span class="font-medium"><?php echo number_format($order['subtotal'], 2, ',', ' '); ?> zł</span>
                                    </div>
                                    <div class="flex justify-between border-b pb-2">
                                        <span class="text-gray-600">Koszt dostawy:</span>
                                        <span class="font-medium"><?php echo number_format($order['shipping_cost'], 2, ',', ' '); ?> zł</span>
                                    </div>
                                    <div class="flex justify-between pt-2 text-lg">
                                        <span class="font-semibold">Razem:</span>
                                        <span class="font-semibold text-primary"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</span>
                                    </div>
                                </div>
                                
                                <?php if ($order['status'] === 'pending' && $order['payment_method'] === 'online'): ?>
                                <div class="mt-4">
                                    <a href="#" class="inline-block w-full bg-primary text-white text-center py-2 px-4 rounded-lg font-medium hover:bg-opacity-90 transition">
                                        Przejdź do płatności
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Przyciski nawigacyjne -->
            <div class="flex justify-between">
                <a href="account.php?tab=orders" class="inline-flex items-center text-primary hover:underline">
                    <i class="ri-arrow-left-line mr-2"></i> Wróć do zamówień
                </a>
                
                <?php if ($order['status'] === 'shipped'): ?>
                <a href="#" class="bg-primary text-white py-2 px-6 rounded-lg font-medium hover:bg-opacity-90 transition">
                    Śledzenie przesyłki
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Modal potwierdzenia anulowania zamówienia -->
<div id="cancelOrderModal" class="fixed inset-0 bg-black bg-opacity-30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-800">Anulowanie zamówienia</h2>
            <button onclick="hideCancelOrderModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <p class="text-gray-500 text-sm mb-6">Czy na pewno chcesz anulować to zamówienie? Tej operacji nie można cofnąć.</p>
        <div class="flex gap-3">
            <button onclick="hideCancelOrderModal()" class="flex-1 bg-gray-100 text-gray-600 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                Wróć
            </button>
            <button id="confirmCancelOrder" class="flex-1 bg-red-500 text-white py-2 rounded-lg text-sm font-medium hover:bg-red-600 transition-colors">
                Anuluj zamówienie
            </button>
        </div>
    </div>
</div>

<!-- Powiadomienie o anulowaniu -->
<div id="cancelNotification" class="fixed bottom-4 right-4 bg-white text-gray-800 px-4 py-3 rounded-lg shadow-lg transform translate-y-full opacity-0 transition-all duration-300 z-50 border border-gray-200">
    <div class="flex items-center">
        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-3">
            <i class="ri-check-line text-red-500"></i>
        </div>
        <span class="text-sm font-medium">Zamówienie zostało anulowane</span>
    </div>
</div>

<?php
// Skrypt JS do obsługi anulowania zamówienia
$extra_js = <<<EOT
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cancelButtons = document.querySelectorAll('.cancel-order-button');
    const cancelOrderModal = document.getElementById('cancelOrderModal');
    const confirmCancelButton = document.getElementById('confirmCancelOrder');
    const cancelNotification = document.getElementById('cancelNotification');
    let currentOrderId = null;
    
    function showCancelOrderModal() {
        cancelOrderModal.classList.remove('hidden');
        cancelOrderModal.classList.add('flex');
    }
    
    function hideCancelOrderModal() {
        cancelOrderModal.classList.remove('flex');
        cancelOrderModal.classList.add('hidden');
    }
    
    function showNotification() {
        cancelNotification.classList.remove('translate-y-full', 'opacity-0');
        setTimeout(() => {
            cancelNotification.classList.add('translate-y-full', 'opacity-0');
        }, 1500);
    }
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            currentOrderId = this.getAttribute('data-order-id');
            showCancelOrderModal();
        });
    });
    
    confirmCancelButton.addEventListener('click', function() {
        if (currentOrderId) {
            // Wysyłanie żądania AJAX do anulowania zamówienia
            fetch('order-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + currentOrderId
            })
            .then(response => response.json())
            .then(responseData => {
                if (responseData.success) {
                    hideCancelOrderModal();
                    showNotification();
                    
                    // Przekierowanie do strony zamówień po 1.5 sekundy
                    setTimeout(() => {
                        window.location.href = 'account.php?tab=orders';
                    }, 1500);
                } else {
                    // Wyświetlenie błędu
                    const errorNotification = document.createElement('div');
                    errorNotification.className = 'fixed bottom-4 right-4 bg-white text-gray-800 px-4 py-3 rounded-lg shadow-lg transform translate-y-full opacity-0 transition-all duration-300 z-50 border border-gray-200';
                    errorNotification.innerHTML = 
                        '<div class="flex items-center">' +
                            '<div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center mr-3">' +
                                '<i class="ri-error-warning-line text-red-500"></i>' +
                            '</div>' +
                            '<span class="text-sm font-medium">' + responseData.message + '</span>' +
                        '</div>';
                    document.body.appendChild(errorNotification);
                    
                    // Pokaż powiadomienie o błędzie
                    requestAnimationFrame(() => {
                        errorNotification.classList.remove('translate-y-full', 'opacity-0');
                    });
                    
                    // Usuń powiadomienie po 3 sekundach
                    setTimeout(() => {
                        errorNotification.classList.add('translate-y-full', 'opacity-0');
                        setTimeout(() => {
                            errorNotification.remove();
                        }, 300);
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas anulowania zamówienia');
            });
        }
    });
    
    // Zamykanie modalu po kliknięciu poza nim
    cancelOrderModal.addEventListener('click', function(e) {
        if (e.target === cancelOrderModal) {
            hideCancelOrderModal();
        }
    });
});
</script>
EOT;

include 'includes/footer.php';
?>
