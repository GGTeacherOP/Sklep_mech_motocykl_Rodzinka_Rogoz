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

$page_title = "Dashboard";

// Przedział czasowy dla danych
$period = isset($_GET['period']) ? $_GET['period'] : 'month';

switch ($period) {
    case 'week':
        $interval = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $period_text = "ostatni tydzień";
        break;
    case 'month':
        $interval = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $period_text = "ostatnie 30 dni";
        break;
    case 'year':
        $interval = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        $period_text = "ostatni rok";
        break;
    default:
        $interval = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        $period_text = "ostatnie 30 dni";
        break;
}

// Pobieranie statystyk
// Sprawdzanie, czy kolumna created_at istnieje w tabeli orders
$check_column_query = "SHOW COLUMNS FROM orders LIKE 'created_at'";
$column_result = $conn->query($check_column_query);
$date_column = ($column_result && $column_result->num_rows > 0) ? 'created_at' : 'order_date';

// Liczba zamówień i przychody dla wykresu sprzedaży w czasie
$sales_query = "SELECT 
                DATE(o.$date_column) as date,
                COUNT(*) as orders_count,
                SUM(o.total_amount) as daily_revenue
                FROM orders o 
                WHERE 1=1 " . str_replace('o.created_at', "o.$date_column", $interval) . "
                GROUP BY DATE(o.$date_column)
                ORDER BY date DESC";
$sales_result = $conn->query($sales_query);
$sales_data = [];
$total_revenue = 0;
$total_orders = 0;

if ($sales_result && $sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $sales_data[] = $row;
        $total_revenue += $row['daily_revenue'];
        $total_orders += $row['orders_count'];
    }
}

// Średni koszyk
$avg_cart_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Przychody według statusu zamówień
$orders_stats_query = "SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as revenue
                FROM orders o
                WHERE 1=1 " . str_replace('o.created_at', "o.$date_column", $interval) . "
                GROUP BY status";
$orders_stats_result = $conn->query($orders_stats_query);
$orders_stats = [
    'pending' => ['count' => 0, 'revenue' => 0],
    'processing' => ['count' => 0, 'revenue' => 0],
    'shipped' => ['count' => 0, 'revenue' => 0],
    'completed' => ['count' => 0, 'revenue' => 0],
    'cancelled' => ['count' => 0, 'revenue' => 0]
];

if ($orders_stats_result && $orders_stats_result->num_rows > 0) {
    while ($row = $orders_stats_result->fetch_assoc()) {
        $orders_stats[$row['status']] = [
            'count' => (int)$row['count'],
            'revenue' => (float)$row['revenue']
        ];
    }
}

// Liczba produktów i stan magazynowy
$products_query = "SELECT 
                COUNT(*) as total_products,
                SUM(stock_quantity) as total_stock,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
                FROM products";
$products_result = $conn->query($products_query);
$products_stats = $products_result->fetch_assoc();

// Liczba użytkowników z podziałem na role
$users_query = "SELECT 
                role,
                COUNT(*) as count
                FROM users
                GROUP BY role";
$users_result = $conn->query($users_query);
$users_stats = [];
$total_users = 0;

if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $users_stats[$row['role']] = (int)$row['count'];
        $total_users += (int)$row['count'];
    }
}

// Liczba nieprzeczytanych wiadomości
$unread_messages_query = "SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'";
$unread_messages_result = $conn->query($unread_messages_query);
$unread_messages = 0;

if ($unread_messages_result && $unread_messages_result->num_rows > 0) {
    $row = $unread_messages_result->fetch_assoc();
    $unread_messages = (int)$row['count'];
}

// Rezerwacje serwisowe
$pending_bookings_query = "SELECT COUNT(*) as count FROM service_bookings WHERE status = 'pending'";
$pending_bookings_result = $conn->query($pending_bookings_query);
$pending_bookings = 0;

if ($pending_bookings_result && $pending_bookings_result->num_rows > 0) {
    $row = $pending_bookings_result->fetch_assoc();
    $pending_bookings = (int)$row['count'];
}

// Ostatnie zamówienia
$recent_orders_query = "SELECT o.*, u.email, u.first_name, u.last_name
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        ORDER BY o.$date_column DESC 
                        LIMIT 6";
$recent_orders_result = $conn->query($recent_orders_query);
$recent_orders = [];

if ($recent_orders_result && $recent_orders_result->num_rows > 0) {
    while ($order = $recent_orders_result->fetch_assoc()) {
        $recent_orders[] = $order;
    }
}

// Najlepiej sprzedające się produkty
$best_selling_query = "SELECT 
                        p.id, 
                        p.name, 
                        p.slug, 
                        p.price, 
                        COUNT(oi.product_id) as sold_count
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        JOIN orders o ON oi.order_id = o.id
                        WHERE 1=1 " . str_replace('o.created_at', "o.$date_column", $interval) . "
                        GROUP BY oi.product_id 
                        ORDER BY sold_count DESC 
                        LIMIT 5";
$best_selling_result = $conn->query($best_selling_query);
$best_selling = [];

if ($best_selling_result && $best_selling_result->num_rows > 0) {
    while ($product = $best_selling_result->fetch_assoc()) {
        $best_selling[] = $product;
    }
}

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Dodanie Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Panel administracyjny</h1>
            <p class="text-gray-600">Witaj, <?php echo $_SESSION['admin_name'] ?? 'Administrator'; ?></p>
        </div>
        
        <div class="mt-4 md:mt-0 flex space-x-2">
            <a href="?period=week" class="px-4 py-2 rounded-md <?php echo $period === 'week' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Tydzień
            </a>
            <a href="?period=month" class="px-4 py-2 rounded-md <?php echo $period === 'month' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Miesiąc
            </a>
            <a href="?period=year" class="px-4 py-2 rounded-md <?php echo $period === 'year' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                Rok
            </a>
        </div>
    </div>
    
    <!-- Karty statystyk -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Przychody -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-4">
                    <i class="ri-money-euro-circle-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Przychody (<?php echo $period_text; ?>)</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($total_revenue, 2, ',', ' '); ?> zł</h3>
                </div>
            </div>
            <div class="mt-4 text-sm">
                <span class="text-green-600"><i class="ri-arrow-up-line"></i> <?php echo $orders_stats['pending']['count'] ?? 0; ?></span>
                <span class="text-gray-500 ml-2">nowych zamówień</span>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-4">
                    <i class="ri-money-dollar-circle-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Średnia wartość zamówienia</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($avg_cart_value ?? 0, 2, ',', ' '); ?> zł</h3>
                </div>
            </div>
            <div class="mt-4 text-sm">
                <span class="text-green-600"><i class="ri-arrow-up-line"></i></span>
                <span class="text-gray-500 ml-2">całkowity przychód</span>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 mr-4">
                    <i class="ri-user-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Użytkownicy</p>
                    <h3 class="text-2xl font-bold"><?php echo $total_users ?? 0; ?></h3>
                </div>
            </div>
            <div class="mt-4 text-sm">
                <span class="text-gray-500">Zarejestrowanych użytkowników</span>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mr-4">
                    <i class="ri-star-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Recenzje</p>
                    <h3 class="text-2xl font-bold">
                    <?php 
                    // Sprawdzamy, czy istnieje tabela recenzji i pobieramy dane
                    $reviews_count = 0;
                    $pending_reviews = 0;
                    
                    $review_tables = ["product_reviews", "reviews", "mechanic_reviews"];
                    $reviews_found = false;
                    
                    foreach ($review_tables as $table) {
                        $table_check = $conn->query("SHOW TABLES LIKE '$table'");
                        if ($table_check && $table_check->num_rows > 0) {
                            $reviews_found = true;
                            $reviews_query = "SELECT COUNT(*) as total_reviews FROM $table";
                            $reviews_result = $conn->query($reviews_query);
                            
                            if ($reviews_result && $reviews_result->num_rows > 0) {
                                $row = $reviews_result->fetch_assoc();
                                $reviews_count += (int)$row['total_reviews'];
                                
                                // Sprawdźmy, czy istnieje kolumna status
                                $column_check = $conn->query("SHOW COLUMNS FROM $table LIKE 'status'");
                                if ($column_check && $column_check->num_rows > 0) {
                                    $pending_query = "SELECT COUNT(*) as pending FROM $table WHERE status = 'pending'"; 
                                    $pending_result = $conn->query($pending_query);
                                    if ($pending_result && $pending_result->num_rows > 0) {
                                        $row = $pending_result->fetch_assoc();
                                        $pending_reviews += (int)$row['pending'];
                                    }
                                }
                            }
                        }
                    }
                    
                    echo $reviews_count;
                    ?>
                    </h3>
                </div>
            </div>
            <div class="mt-4 text-sm">
                <span class="text-yellow-600"><i class="ri-time-line"></i> <?php echo $pending_reviews; ?></span>
                <span class="text-gray-500 ml-2">oczekujących recenzji</span>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Ostatnie zamówienia -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Ostatnie zamówienia</h2>
            
            <?php if (!empty($recent_orders)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Numer</th>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase">Kwota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2">
                                <a href="order.php?id=<?php echo $order['id']; ?>" class="text-blue-600 hover:underline">
                                    <?php echo $order['order_number']; ?>
                                </a>
                            </td>
                            <td class="py-2 text-gray-500"><?php echo date('d.m.Y', strtotime($order['order_date'])); ?></td>
                            <td class="py-2">
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td class="py-2 font-medium"><?php echo number_format($order['total'], 2, ',', ' '); ?> zł</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-right">
                <a href="orders.php" class="text-blue-600 hover:underline text-sm font-medium">Zobacz wszystkie zamówienia</a>
            </div>
            <?php else: ?>
            <p class="text-gray-500">Brak zamówień</p>
            <?php endif; ?>
        </div>
        
        <!-- Najlepiej sprzedające się produkty -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Najlepiej sprzedające się produkty</h2>
            
            <?php if (!empty($best_selling)): ?>
            <div class="space-y-4">
                <?php foreach ($best_selling as $product): ?>
                <div class="flex items-center justify-between">
                    <div>
                        <a href="../product.php?slug=<?php echo $product['slug']; ?>" target="_blank" class="text-blue-600 hover:underline">
                            <?php echo $product['name']; ?>
                        </a>
                    </div>
                    <div class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-medium">
                        <?php echo $product['sold_count']; ?> sprzedanych
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 text-right">
                <a href="products.php" class="text-blue-600 hover:underline text-sm font-medium">Zarządzaj produktami</a>
            </div>
            <?php else: ?>
            <p class="text-gray-500">Brak danych sprzedażowych</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Szybkie linki -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="orders.php?status=pending" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 mr-4">
                    <i class="ri-time-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Oczekujące zamówienia</p>
                    <h3 class="text-xl font-bold"><?php echo $orders_stats['pending_orders'] ?? 0; ?></h3>
                </div>
            </div>
        </a>
        
        <a href="products.php?stock=low" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-600 mr-4">
                    <i class="ri-alert-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Niski stan magazynowy</p>
                    <h3 class="text-xl font-bold">...</h3>
                </div>
            </div>
        </a>
        
        <a href="reviews.php?status=pending" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 mr-4">
                    <i class="ri-star-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Recenzje do moderacji</p>
                    <h3 class="text-xl font-bold"><?php echo $reviews_stats['pending_reviews'] ?? 0; ?></h3>
                </div>
            </div>
        </a>
        
        <a href="products.php?action=add" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-600 mr-4">
                    <i class="ri-add-line text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Dodaj nowy produkt</p>
                    <h3 class="text-xl font-bold">+</h3>
                </div>
            </div>
        </a>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
