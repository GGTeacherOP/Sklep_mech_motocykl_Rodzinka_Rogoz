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

$page_title = "Analityka";

// Sprawdzenie, czy tabela orders istnieje
$tables_query = "SHOW TABLES LIKE 'orders'";
$tables_result = $conn->query($tables_query);
$orders_table_exists = $tables_result && $tables_result->num_rows > 0;

// Flaga wskazująca, czy mamy jakiekolwiek zamówienia
$has_orders = false;
$monthly_data = [
    'labels' => [],
    'orders' => [],
    'revenue' => []
];

$months = [
    1 => 'Styczeń',
    2 => 'Luty',
    3 => 'Marzec',
    4 => 'Kwiecień',
    5 => 'Maj',
    6 => 'Czerwiec',
    7 => 'Lipiec',
    8 => 'Sierpień',
    9 => 'Wrzesień',
    10 => 'Październik',
    11 => 'Listopad',
    12 => 'Grudzień'
];

// Inicjalizacja danych dla wszystkich miesięcy
for ($i = 1; $i <= 12; $i++) {
    $monthly_data['labels'][] = $months[$i];
    $monthly_data['orders'][] = 0;
    $monthly_data['revenue'][] = 0;
}

if ($orders_table_exists) {
    // Pobranie danych dla wykresu sprzedaży miesięcznej
    $current_year = date('Y');
    $monthly_sales_query = "SELECT 
                              MONTH(created_at) as month, 
                              COUNT(*) as order_count, 
                              SUM(total_amount) as revenue
                            FROM orders 
                            WHERE YEAR(created_at) = $current_year
                            GROUP BY MONTH(created_at)
                            ORDER BY MONTH(created_at)";

    $monthly_result = $conn->query($monthly_sales_query);

    if ($monthly_result && $monthly_result->num_rows > 0) {
        $has_orders = true;
        while ($row = $monthly_result->fetch_assoc()) {
            $month_index = (int)$row['month'] - 1; // Indeks tablicy zaczyna się od 0
            $monthly_data['orders'][$month_index] = (int)$row['order_count'];
            $monthly_data['revenue'][$month_index] = (float)$row['revenue'];
        }
    }
    
    // Pobranie danych o statusach zamówień
    $status_data = [
        'labels' => [],
        'counts' => [],
        'colors' => [
            'pending' => '#FBBF24',     // Żółty
            'processing' => '#3B82F6',  // Niebieski
            'shipped' => '#8B5CF6',     // Fioletowy
            'delivered' => '#10B981',   // Zielony
            'cancelled' => '#EF4444'    // Czerwony
        ]
    ];
    
    if ($has_orders) {
        $order_status_query = "SELECT 
                                status, 
                                COUNT(*) as count 
                              FROM 
                                orders 
                              GROUP BY 
                                status";
        
        $status_result = $conn->query($order_status_query);
        
        if ($status_result && $status_result->num_rows > 0) {
            while ($row = $status_result->fetch_assoc()) {
                $status_data['labels'][] = ucfirst($row['status']);
                $status_data['counts'][] = (int)$row['count'];
            }
        }
    }
}

// Pobranie danych o kategoriach produktów
$categories_data = [
    'labels' => [],
    'counts' => []
];

$categories_query = "SELECT 
                      c.name, 
                      COUNT(p.id) as product_count
                    FROM 
                      categories c
                    LEFT JOIN 
                      products p ON c.id = p.category_id
                    GROUP BY 
                      c.id
                    ORDER BY 
                      product_count DESC
                    LIMIT 10";

$categories_result = $conn->query($categories_query);

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories_data['labels'][] = $row['name'];
        $categories_data['counts'][] = (int)$row['product_count'];
    }
}

// Pobranie najlepiej sprzedających się produktów
$top_products = [];

if ($orders_table_exists && $has_orders) {
    $top_products_query = "SELECT 
                            p.id,
                            p.name,
                            SUM(oi.quantity) as total_quantity,
                            SUM(oi.quantity * oi.price) as total_revenue
                          FROM 
                            order_items oi
                          LEFT JOIN 
                            products p ON oi.product_id = p.id
                          GROUP BY 
                            p.id
                          ORDER BY 
                            total_quantity DESC
                          LIMIT 5";
    
    $top_products_result = $conn->query($top_products_query);
    
    if ($top_products_result && $top_products_result->num_rows > 0) {
        while ($row = $top_products_result->fetch_assoc()) {
            $top_products[] = $row;
        }
    }
}

include 'includes/header.php';
?>

<div class="admin-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Główna zawartość -->
    <div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Analityka</h1>
        <p class="text-gray-600">Szczegółowe dane o wydajności sklepu</p>
    </div>
    
    <!-- Karty statystyk -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mr-4">
                    <i class="ri-shopping-bag-line text-blue-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Zamówienia</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?php 
                        $orders_count = 0;
                        if ($orders_table_exists) {
                            $count_query = "SELECT COUNT(*) as count FROM orders";
                            $count_result = $conn->query($count_query);
                            if ($count_result && $count_result->num_rows > 0) {
                                $orders_count = $count_result->fetch_assoc()['count'];
                            }
                        }
                        echo $orders_count;
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mr-4">
                    <i class="ri-money-dollar-circle-line text-green-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Przychód</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?php 
                        $total_revenue = 0;
                        if ($orders_table_exists) {
                            $revenue_query = "SELECT SUM(total_amount) as revenue FROM orders";
                            $revenue_result = $conn->query($revenue_query);
                            if ($revenue_result && $revenue_result->num_rows > 0) {
                                $total_revenue = $revenue_result->fetch_assoc()['revenue'] ?? 0;
                            }
                        }
                        echo number_format($total_revenue, 2, ',', ' ') . ' zł';
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mr-4">
                    <i class="ri-box-3-line text-purple-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Produkty</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?php 
                        $products_count = 0;
                        $products_query = "SELECT COUNT(*) as count FROM products";
                        $products_result = $conn->query($products_query);
                        if ($products_result && $products_result->num_rows > 0) {
                            $products_count = $products_result->fetch_assoc()['count'];
                        }
                        echo $products_count;
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                    <i class="ri-user-line text-yellow-500 text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Klienci</p>
                    <h3 class="text-2xl font-bold text-gray-800">
                        <?php 
                        $users_count = 0;
                        $users_query = "SELECT COUNT(*) as count FROM users WHERE role = 'user'";
                        $users_result = $conn->query($users_query);
                        if ($users_result && $users_result->num_rows > 0) {
                            $users_count = $users_result->fetch_assoc()['count'];
                        }
                        echo $users_count;
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Wykres sprzedaży miesięcznej -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Sprzedaż miesięczna w <?php echo date('Y'); ?></h2>
            <div style="height: 300px;">
                <?php if ($orders_table_exists && $has_orders): ?>
                <canvas id="monthlySalesChart"></canvas>
                <?php else: ?>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <div class="text-gray-400 text-5xl mb-4"><i class="ri-line-chart-line"></i></div>
                        <p class="text-gray-500 mb-2">Brak danych sprzedażowych</p>
                        <p class="text-gray-400 text-sm">Dane pojawią się po pierwszych zamówieniach</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Wykres statusów zamówień -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Statusy zamówień</h2>
            <div style="height: 300px;">
                <?php if ($orders_table_exists && $has_orders): ?>
                <canvas id="orderStatusChart"></canvas>
                <?php else: ?>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <div class="text-gray-400 text-5xl mb-4"><i class="ri-pie-chart-line"></i></div>
                        <p class="text-gray-500 mb-2">Brak danych o statusach zamówień</p>
                        <p class="text-gray-400 text-sm">Dane pojawią się po pierwszych zamówieniach</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Najlepiej sprzedające się produkty -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Najlepiej sprzedające się produkty</h2>
            <?php if (!empty($top_products)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-700">Produkt</th>
                            <th class="px-4 py-2 text-right text-gray-700">Sprzedanych</th>
                            <th class="px-4 py-2 text-right text-gray-700">Przychód</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td class="px-4 py-2 border-t"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td class="px-4 py-2 border-t text-right"><?php echo $product['total_quantity']; ?> szt.</td>
                            <td class="px-4 py-2 border-t text-right"><?php echo number_format($product['total_revenue'], 2, ',', ' '); ?> zł</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    <div class="text-gray-400 text-5xl mb-4"><i class="ri-shopping-basket-line"></i></div>
                    <p class="text-gray-500 mb-2">Brak danych o sprzedaży produktów</p>
                    <p class="text-gray-400 text-sm">Dane pojawią się po pierwszych zamówieniach</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Produkty według kategorii -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-4">Produkty według kategorii</h2>
            <div style="height: 300px;">
                <?php if (!empty($categories_data['labels'])): ?>
                <canvas id="categoriesChart"></canvas>
                <?php else: ?>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <div class="text-gray-400 text-5xl mb-4"><i class="ri-bar-chart-grouped-line"></i></div>
                        <p class="text-gray-500 mb-2">Brak danych o kategoriach produktów</p>
                        <p class="text-gray-400 text-sm">Dodaj kategorie i produkty, aby zobaczyć statystyki</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Konfiguracja wykresów
Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
Chart.defaults.font.size = 13;
Chart.defaults.color = '#6B7280';

<?php if ($orders_table_exists && $has_orders): ?>
// Wykres sprzedaży miesięcznej
const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
const monthlySalesChart = new Chart(monthlySalesCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($monthly_data['labels']); ?>,
        datasets: [
            {
                label: 'Przychód (zł)',
                data: <?php echo json_encode($monthly_data['revenue']); ?>,
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                yAxisID: 'y',
                fill: true
            },
            {
                label: 'Liczba zamówień',
                data: <?php echo json_encode($monthly_data['orders']); ?>,
                borderColor: '#8B5CF6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                yAxisID: 'y1',
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Przychód (zł)'
                }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false
                },
                title: {
                    display: true,
                    text: 'Liczba zamówień'
                }
            }
        },
        interaction: {
            mode: 'index',
            intersect: false
        }
    }
});

// Wykres statusów zamówień
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
const orderStatusChart = new Chart(orderStatusCtx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($status_data['labels']); ?>,
        datasets: [{
            data: <?php echo json_encode($status_data['counts']); ?>,
            backgroundColor: Object.values(<?php echo json_encode($status_data['colors']); ?>)
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($categories_data['labels'])): ?>
// Wykres kategorii produktów
const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
const categoriesChart = new Chart(categoriesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($categories_data['labels']); ?>,
        datasets: [{
            label: 'Liczba produktów',
            data: <?php echo json_encode($categories_data['counts']); ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.6)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php
include 'includes/footer.php';
?>
