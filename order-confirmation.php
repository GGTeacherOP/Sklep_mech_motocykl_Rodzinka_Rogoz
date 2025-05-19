<?php
$page_title = "Potwierdzenie zamówienia | MotoShop";
require_once 'includes/config.php';

// Sprawdzanie czy ID zamówienia zostało przekazane
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = (int)$_GET['order_id'];

// Pobieranie danych zamówienia
$order_query = "SELECT * FROM orders WHERE id = $order_id";
$order_result = $conn->query($order_query);

if (!$order_result || $order_result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$order = $order_result->fetch_assoc();

// Pobieranie pozycji zamówienia
$items_query = "SELECT oi.*, p.name, p.slug FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
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

// Instrukcje płatności
$payment_instructions = '';
if ($order['payment_method'] === 'transfer') {
    $payment_instructions = '
    <div class="bg-blue-50 p-4 rounded-lg mb-8">
        <h3 class="font-semibold text-blue-800 mb-2">Dane do przelewu:</h3>
        <p class="mb-1"><strong>Nazwa odbiorcy:</strong> MotoShop Sp. z o.o.</p>
        <p class="mb-1"><strong>Nr rachunku:</strong> 12 3456 7890 1234 5678 9012 3456</p>
        <p class="mb-1"><strong>Tytuł przelewu:</strong> Zamówienie ' . $order['order_number'] . '</p>
        <p class="mb-1"><strong>Kwota:</strong> ' . number_format($order['total'], 2, ',', ' ') . ' zł</p>
    </div>';
} elseif ($order['payment_method'] === 'online') {
    $payment_instructions = '
    <div class="bg-green-50 p-4 rounded-lg mb-8">
        <h3 class="font-semibold text-green-800 mb-2">Płatność online:</h3>
        <p class="mb-4">Za chwilę zostaniesz przekierowany do systemu płatności Przelewy24, gdzie będziesz mógł dokonać płatności za zamówienie.</p>
        <a href="#" class="bg-primary text-white py-2 px-4 rounded-lg inline-block hover:bg-opacity-90 transition">Przejdź do płatności</a>
    </div>';
}

include 'includes/header.php';
?>

<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="bg-white rounded-lg shadow-sm p-8 max-w-3xl mx-auto">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                        <i class="ri-check-line text-3xl text-green-600"></i>
                    </div>
                    <h1 class="text-3xl font-bold mb-2">Dziękujemy za zamówienie!</h1>
                    <p class="text-gray-600">Twoje zamówienie zostało przyjęte do realizacji.</p>
                </div>
                
                <div class="border-b border-gray-200 pb-6 mb-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Numer zamówienia: <?php echo $order['order_number']; ?></h2>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <p class="text-gray-600">Data złożenia: <?php echo date('d.m.Y H:i', strtotime($order['order_date'])); ?></p>
                </div>
                
                <?php echo $payment_instructions; ?>
                
                <div class="mb-8">
                    <h2 class="text-lg font-semibold mb-4">Szczegóły zamówienia</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Produkt
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cena
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ilość
                                    </th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Razem
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($order_items as $item): 
                                    $item_total = $item['price'] * $item['quantity'];
                                ?>
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <a href="product.php?slug=<?php echo $item['slug']; ?>" class="text-sm font-medium text-gray-900 hover:text-primary">
                                            <?php echo $item['name']; ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo number_format($item['price'], 2, ',', ' '); ?> zł</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $item['quantity']; ?></div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-primary"><?php echo number_format($item_total, 2, ',', ' '); ?> zł</div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 border-t border-gray-200 pt-4">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600">Wartość produktów:</span>
                            <span class="font-medium"><?php echo number_format($order['subtotal'], 2, ',', ' '); ?> zł</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600">Koszt dostawy:</span>
                            <span class="font-medium"><?php echo number_format($order['shipping_cost'], 2, ',', ' '); ?> zł</span>
                        </div>
                        <div class="flex justify-between items-center py-2 text-lg font-bold">
                            <span>Razem:</span>
                            <span class="text-primary"><?php echo number_format($order['total'], 2, ',', ' '); ?> zł</span>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="font-semibold mb-3">Dane zamawiającego</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="mb-1"><?php echo $order['first_name'] . ' ' . $order['last_name']; ?></p>
                            <p class="mb-1"><?php echo $order['email']; ?></p>
                            <p class="mb-1"><?php echo $order['phone']; ?></p>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-3">Adres dostawy</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="mb-1"><?php echo $order['address']; ?></p>
                            <p class="mb-1"><?php echo $order['postal_code'] . ' ' . $order['city']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="font-semibold mb-3">Metoda dostawy</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p><?php echo $shipping_method_text; ?></p>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold mb-3">Metoda płatności</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p><?php echo $payment_method_text; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="index.php" class="inline-block bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-opacity-90 transition">
                        Wróć do strony głównej
                    </a>
                    <?php if (isLoggedIn()): ?>
                    <a href="account.php?tab=orders" class="inline-block bg-gray-100 text-gray-800 py-3 px-6 rounded-lg font-medium hover:bg-gray-200 transition ml-4">
                        Moje zamówienia
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>
