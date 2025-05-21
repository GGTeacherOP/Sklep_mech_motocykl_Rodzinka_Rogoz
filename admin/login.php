<?php
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

// Obsługa logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Wszystkie pola są wymagane.';
    } else {
        // Sprawdzenie, czy użytkownik istnieje i ma uprawnienia administratora
        $query = "SELECT * FROM users WHERE email = '$email' AND role = 'admin'";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Weryfikacja hasła
            if (password_verify($password, $user['password'])) {
                // Logowanie pomyślne
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['admin_email'] = $user['email'];
                
                // Przekierowanie do panelu administracyjnego
                header("Location: index.php");
                exit;
            } else {
                $error_message = 'Nieprawidłowe hasło.';
            }
        } else {
            $error_message = 'Nieprawidłowy email lub brak uprawnień administratora.';
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
