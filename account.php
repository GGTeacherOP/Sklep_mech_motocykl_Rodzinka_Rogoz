<?php
$page_title = "Moje konto | MotoShop";
require_once 'includes/config.php';

// Sprawdzanie czy użytkownik jest zalogowany
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];

// Pobieranie danych użytkownika
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if (!$user_result || $user_result->num_rows === 0) {
    header("Location: logout.php");
    exit;
}

$user = $user_result->fetch_assoc();
$user_stmt->close();

// Pobieranie listy zamówień użytkownika
$orders_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
$orders_stmt->close();

$orders = [];
if ($orders_result && $orders_result->num_rows > 0) {
    while ($order = $orders_result->fetch_assoc()) {
        $orders[] = $order;
    }
}

// Obsługa aktualizacji danych użytkownika
$update_message = '';

if (isset($_POST['update_profile'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $postal_code = sanitize($_POST['postal_code']);
    
    $update_stmt = $conn->prepare("UPDATE users SET 
                    first_name = ?, 
                    last_name = ?, 
                    phone = ?, 
                    address = ?, 
                    city = ?, 
                    postal_code = ? 
                    WHERE id = ?");
    $update_stmt->bind_param("ssssssi", $first_name, $last_name, $phone, $address, $city, $postal_code, $user_id);
    
    if ($update_stmt->execute()) {
        $update_message = 'Twoje dane zostały zaktualizowane.';
        
        // Odświeżenie danych użytkownika
        $user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user = $user_result->fetch_assoc();
        $user_stmt->close();
    } else {
        $update_message = 'Wystąpił błąd podczas aktualizacji danych.';
    }
    $update_stmt->close();
}

// Obsługa zmiany hasła
$password_message = '';

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Sprawdzanie poprawności obecnego hasła
    if (!password_verify($current_password, $user['password'])) {
        $password_message = 'Obecne hasło jest nieprawidłowe.';
    } elseif ($new_password !== $confirm_password) {
        $password_message = 'Nowe hasło i potwierdzenie hasła nie są zgodne.';
    } elseif (strlen($new_password) < 6) {
        $password_message = 'Nowe hasło powinno mieć co najmniej 6 znaków.';
    } else {
        // Haszowanie nowego hasła
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $password_update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $password_update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($password_update_stmt->execute()) {
            $password_message = 'Twoje hasło zostało zaktualizowane.';
        } else {
            $password_message = 'Wystąpił błąd podczas aktualizacji hasła.';
        }
        $password_update_stmt->close();
    }
}

// Określanie aktywnej zakładki
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$valid_tabs = ['profile', 'orders', 'password'];

if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'profile';
}

include 'includes/header.php';
?>

<main>
    <div class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-3">Moje konto</h1>
                <nav class="flex">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="index.php" class="text-gray-700 hover:text-primary">
                                Strona główna
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="ri-arrow-right-s-line text-gray-500 mx-2"></i>
                                <span class="text-primary font-medium">Moje konto</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <?php if (!empty($update_message)): ?>
            <div class="bg-green-50 text-green-800 rounded-lg p-4 mb-8">
                <?php echo $update_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($password_message)): ?>
            <div class="<?php echo strpos($password_message, 'błąd') !== false || strpos($password_message, 'nieprawidłowe') !== false ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800'; ?> rounded-lg p-4 mb-8">
                <?php echo $password_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Menu nawigacyjne -->
                <div class="lg:w-3/12">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="mb-6">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                    <i class="ri-user-line text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="font-semibold"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></p>
                                    <p class="text-sm text-gray-500"><?php echo $user['email']; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <nav>
                            <ul class="space-y-2">
                                <li>
                                    <a href="?tab=profile" class="block py-2 px-4 rounded-lg <?php echo $active_tab === 'profile' ? 'bg-primary text-white' : 'hover:bg-gray-100'; ?>">
                                        <i class="ri-user-settings-line mr-2"></i> Moje dane
                                    </a>
                                </li>
                                <li>
                                    <a href="?tab=orders" class="block py-2 px-4 rounded-lg <?php echo $active_tab === 'orders' ? 'bg-primary text-white' : 'hover:bg-gray-100'; ?>">
                                        <i class="ri-shopping-bag-line mr-2"></i> Moje zamówienia
                                    </a>
                                </li>
                                <li>
                                    <a href="?tab=password" class="block py-2 px-4 rounded-lg <?php echo $active_tab === 'password' ? 'bg-primary text-white' : 'hover:bg-gray-100'; ?>">
                                        <i class="ri-lock-line mr-2"></i> Zmiana hasła
                                    </a>
                                </li>
                                <?php if ($user['role'] === 'admin'): ?>
                                <li>
                                    <a href="admin/index.php" class="block py-2 px-4 rounded-lg hover:bg-gray-100">
                                        <i class="ri-settings-line mr-2"></i> Panel admina
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li>
                                    <a href="logout.php" class="block py-2 px-4 rounded-lg text-red-600 hover:bg-red-50">
                                        <i class="ri-logout-box-r-line mr-2"></i> Wyloguj się
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                
                <!-- Główna zawartość -->
                <div class="lg:w-9/12">
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <?php if ($active_tab === 'profile'): ?>
                        <!-- Dane użytkownika -->
                        <div>
                            <h2 class="text-xl font-semibold mb-6">Moje dane</h2>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">Imię</label>
                                        <input type="text" id="first_name" name="first_name" value="<?php echo $user['first_name']; ?>" required
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko</label>
                                        <input type="text" id="last_name" name="last_name" value="<?php echo $user['last_name']; ?>" required
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" id="email" value="<?php echo $user['email']; ?>" disabled
                                            class="w-full border-gray-300 bg-gray-50 rounded-lg">
                                        <p class="mt-1 text-xs text-gray-500">Adres email nie może być zmieniony</p>
                                    </div>
                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo $user['phone']; ?>"
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                                
                                <h3 class="text-lg font-medium mb-4 mt-8">Adres dostawy</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
                                        <input type="text" id="address" name="address" value="<?php echo $user['address']; ?>"
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Miejscowość</label>
                                        <input type="text" id="city" name="city" value="<?php echo $user['city']; ?>"
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Kod pocztowy</label>
                                        <input type="text" id="postal_code" name="postal_code" value="<?php echo $user['postal_code']; ?>"
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                </div>
                                
                                <div class="mt-8">
                                    <button type="submit" name="update_profile" class="bg-primary text-white py-2 px-6 rounded-lg font-medium hover:bg-opacity-90 transition">
                                        Zapisz zmiany
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <?php elseif ($active_tab === 'orders'): ?>
                        <!-- Historia zamówień -->
                        <div>
                            <h2 class="text-xl font-semibold mb-6">Moje zamówienia</h2>
                            
                            <?php if (empty($orders)): ?>
                            <div class="text-center py-12">
                                <div class="text-gray-500 mb-4"><i class="ri-shopping-bag-line text-5xl"></i></div>
                                <h3 class="text-xl font-semibold mb-2">Brak zamówień</h3>
                                <p class="text-gray-500 mb-6">Nie złożyłeś jeszcze żadnych zamówień.</p>
                                <a href="ProductCatalog.php" class="inline-block bg-primary text-white py-2 px-6 rounded-lg font-medium hover:bg-opacity-90 transition">
                                    Przeglądaj produkty
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Numer zamówienia
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Data
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Kwota
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                <span class="sr-only">Akcje</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($orders as $order): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo $order['order_number']; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
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
                                                ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-primary"><?php echo number_format($order['total_amount'], 2, ',', ' '); ?> zł</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="text-primary hover:text-primary-dark">
                                                    Szczegóły
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php elseif ($active_tab === 'password'): ?>
                        <!-- Zmiana hasła -->
                        <div>
                            <h2 class="text-xl font-semibold mb-6">Zmiana hasła</h2>
                            
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) . '?tab=password'; ?>">
                                <div class="space-y-4 max-w-md">
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Obecne hasło</label>
                                        <input type="password" id="current_password" name="current_password" required
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nowe hasło</label>
                                        <input type="password" id="new_password" name="new_password" required
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Powtórz nowe hasło</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required
                                            class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                    </div>
                                    <div class="pt-4">
                                        <button type="submit" name="change_password" class="bg-primary text-white py-2 px-6 rounded-lg font-medium hover:bg-opacity-90 transition">
                                            Zmień hasło
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
include 'includes/footer.php';
?>
