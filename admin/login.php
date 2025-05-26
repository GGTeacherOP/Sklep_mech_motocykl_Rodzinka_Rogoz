<?php
// Włączenie wyświetlania błędów PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$page_title = "Panel administracyjny - Logowanie";

// Sprawdzenie, czy użytkownik jest już zalogowany
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Ścieżka do głównego katalogu
$base_path = dirname(__DIR__);
require_once $base_path . '/includes/config.php';

$error_message = '';

// Sprawdzanie czy tabela admins istnieje (tymczasowe rozwiązanie - pokaże nam czy problem dotyczy braku tabeli)
$check_table_query = "SHOW TABLES LIKE 'admins'";
$table_result = $conn->query($check_table_query);
$admins_table_exists = ($table_result && $table_result->num_rows > 0);

// Jeśli tabela admins nie istnieje, sprawdź, czy możemy zalogować się przez tabelę users
$use_users_table = !$admins_table_exists;

// Obsługa logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Możliwość logowania przez email lub nazwę użytkownika
    $login = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $success = false;
    
    if (empty($login) || empty($password)) {
        $error_message = 'Wszystkie pola są wymagane.';
    } else {
        try {
            if ($use_users_table) {
                // Jeśli tabela admins nie istnieje, próbujemy zalogować się przez users
                $query = "SELECT * FROM users WHERE email = ? AND (role = 'admin' OR role = 'mechanic' OR role = 'owner')";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $login);
            } else {
                // Logowanie przez tabelę admins
                $query = "SELECT * FROM admins WHERE (email = ? OR username = ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $login, $login);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Weryfikacja hasła
                if (password_verify($password, $user['password'])) {
                    // Logowanie pomyślne
                    $_SESSION['admin_id'] = $user['id'];
                    
                    if ($use_users_table) {
                        $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_role'] = $user['role'] ?? 'admin';
                    } else {
                        $_SESSION['admin_name'] = $user['name'];
                        $_SESSION['admin_email'] = $user['email'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['admin_role'] = $user['role'] ?? 'admin';
                    }
                    
                    $success = true;
                } else {
                    $error_message = 'Nieprawidłowe hasło.';
                }
            } else {
                $error_message = 'Nieprawidłowy login lub brak konta administratora.';
            }
        } catch (Exception $e) {
            $error_message = 'Błąd podczas logowania: ' . $e->getMessage();
        }
        
        if ($success) {
            // Przekierowanie do panelu administracyjnego
            header("Location: index.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="w-full max-w-md p-8 bg-white rounded-lg shadow-md">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Panel administracyjny</h1>
                <p class="text-gray-600">MotoShop</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-50 text-red-800 rounded-lg p-4 mb-6">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="mb-6">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                    <input type="password" id="password" name="password" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition">
                    Zaloguj się
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="../index.php" class="text-blue-600 hover:underline">Wróć do strony głównej</a>
            </div>
        </div>
    </div>
</body>
</html>
