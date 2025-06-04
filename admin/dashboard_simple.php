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

$page_title = "Uproszczony Dashboard";

// Dołączenie nagłówka
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Główna zawartość -->
<div class="admin-content ml-0 lg:ml-260 p-4 md:p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Panel Administratora - Wersja Uproszczona</h1>
        <p class="text-gray-600">Witaj, <?php echo $_SESSION['admin_name'] ?? 'Administrator'; ?>!</p>
        <div class="mt-2 text-sm text-red-600">
            To jest uproszczona wersja panelu administracyjnego, która pomoże zdiagnozować problemy z podstawową wersją.
        </div>
    </div>
    
    <!-- Informacje systemowe -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Informacje systemowe</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-md font-medium mb-2">PHP</h3>
                <p><strong>Wersja PHP:</strong> <?php echo phpversion(); ?></p>
                <p><strong>Rozszerzenia:</strong></p>
                <ul class="list-disc list-inside text-sm">
                    <li>PDO: <?php echo extension_loaded('pdo') ? '<span class="text-green-600">Włączone</span>' : '<span class="text-red-600">Wyłączone</span>'; ?></li>
                    <li>MySQLi: <?php echo extension_loaded('mysqli') ? '<span class="text-green-600">Włączone</span>' : '<span class="text-red-600">Wyłączone</span>'; ?></li>
                    <li>GD: <?php echo extension_loaded('gd') ? '<span class="text-green-600">Włączone</span>' : '<span class="text-red-600">Wyłączone</span>'; ?></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-md font-medium mb-2">MySQL</h3>
                <?php
                $mysql_version = $conn->query('SELECT VERSION() as version')->fetch_assoc();
                ?>
                <p><strong>Wersja MySQL:</strong> <?php echo $mysql_version['version'] ?? 'Nieznana'; ?></p>
                <p><strong>Status połączenia:</strong> <span class="text-green-600">Połączony</span></p>
            </div>
        </div>
    </div>
    
    <!-- Podstawowe dane -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Podstawowe dane sklepu</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php
            // Tabela Products
            $products_query = "SHOW TABLES LIKE 'products'";
            $products_exists = $conn->query($products_query)->num_rows > 0;
            
            if ($products_exists) {
                $products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
                ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium">Produkty</h3>
                    <p class="text-2xl font-bold"><?php echo $products_count; ?></p>
                </div>
                <?php
            } else {
                ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium">Produkty</h3>
                    <p class="text-sm text-red-600">Tabela nie istnieje</p>
                </div>
                <?php
            }
            
            // Tabela Users
            $users_query = "SHOW TABLES LIKE 'users'";
            $users_exists = $conn->query($users_query)->num_rows > 0;
            
            if ($users_exists) {
                $users_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium">Użytkownicy</h3>
                    <p class="text-2xl font-bold"><?php echo $users_count; ?></p>
                </div>
                <?php
            } else {
                ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium">Użytkownicy</h3>
                    <p class="text-sm text-red-600">Tabela nie istnieje</p>
                </div>
                <?php
            }
            
            // Tabela Orders
            $orders_query = "SHOW TABLES LIKE 'orders'";
            $orders_exists = $conn->query($orders_query)->num_rows > 0;
            
            if ($orders_exists) {
                $orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
                ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium">Zamówienia</h3>
                    <p class="text-2xl font-bold"><?php echo $orders_count; ?></p>
                </div>
                <?php
            } else {
                ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-medium">Zamówienia</h3>
                    <p class="text-sm text-red-600">Tabela nie istnieje</p>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
    
    <!-- Struktura tabeli orders -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Struktura tabeli Orders</h2>
        
        <?php
        if ($orders_exists) {
            $structure_query = "DESCRIBE orders";
            $structure_result = $conn->query($structure_query);
            
            if ($structure_result && $structure_result->num_rows > 0) {
                ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kolumna</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Null</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Klucz</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Domyślna</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $structure_result->fetch_assoc()) { ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php echo $row['Field']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $row['Type']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $row['Null']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $row['Key']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $row['Default'] ?? 'NULL'; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <?php
            } else {
                echo '<p class="text-red-600">Nie można pobrać struktury tabeli orders.</p>';
            }
        } else {
            echo '<p class="text-red-600">Tabela orders nie istnieje.</p>';
        }
        ?>
    </div>
    
    <!-- Dodatkowo szybkie linki -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="index_backup.php" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition flex flex-col items-center justify-center text-center">
            <i class="ri-dashboard-line text-3xl text-blue-600 mb-2"></i>
            <h3 class="font-medium mb-1">Oryginalny Dashboard</h3>
            <p class="text-sm text-gray-500">Przejdź do oryginalnego dashboardu</p>
        </a>
        
        <a href="products.php" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition flex flex-col items-center justify-center text-center">
            <i class="ri-shopping-bag-line text-3xl text-green-600 mb-2"></i>
            <h3 class="font-medium mb-1">Zarządzanie Produktami</h3>
            <p class="text-sm text-gray-500">Dodawaj i edytuj produkty</p>
        </a>
        
        <a href="orders.php" class="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition flex flex-col items-center justify-center text-center">
            <i class="ri-file-list-3-line text-3xl text-purple-600 mb-2"></i>
            <h3 class="font-medium mb-1">Zarządzanie Zamówieniami</h3>
            <p class="text-sm text-gray-500">Przeglądaj i zarządzaj zamówieniami</p>
        </a>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
