<?php
// Strona ustawiania nowego hasła
$page_title = "Nowe hasło";
require_once 'includes/config.php';

// Sprawdzenie czy użytkownik jest już zalogowany
if (isLoggedIn()) {
    redirect('account.php');
}

$message = '';
$message_type = '';
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';

// Sprawdzenie czy token jest ważny
if (empty($token)) {
    $message = 'Nieprawidłowy link do resetowania hasła';
    $message_type = 'error';
} else {
    $sql = "SELECT id FROM users WHERE reset_token = '$token' AND reset_token_expires > NOW()";
    $result = $conn->query($sql);
    
    if (!$result || $result->num_rows === 0) {
        $message = 'Link do resetowania hasła wygasł lub jest nieprawidłowy';
        $message_type = 'error';
    }
}

// Obsługa formularza ustawiania nowego hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $message = 'Wypełnij wszystkie pola';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Hasła nie są identyczne';
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $message = 'Hasło musi mieć co najmniej 8 znaków';
        $message_type = 'error';
    } else {
        // Sprawdzenie czy token jest nadal ważny
        $sql = "SELECT id FROM users WHERE reset_token = '$token' AND reset_token_expires > NOW()";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            // Hashowanie nowego hasła
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Aktualizacja hasła i usunięcie tokenu
            $update_sql = "UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_token_expires = NULL WHERE id = $user_id";
            
            if ($conn->query($update_sql)) {
                $message = 'Hasło zostało zmienione. Możesz się teraz zalogować.';
                $message_type = 'success';
                
                // Przekierowanie do strony logowania po 3 sekundach
                header("refresh:3;url=login.php");
            } else {
                $message = 'Wystąpił błąd podczas zmiany hasła';
                $message_type = 'error';
            }
        } else {
            $message = 'Link do resetowania hasła wygasł lub jest nieprawidłowy';
            $message_type = 'error';
        }
    }
}

include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow-sm p-8">
        <h1 class="text-3xl font-bold text-center mb-8">Ustaw nowe hasło</h1>
        
        <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($message_type !== 'error'): ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?token=' . $token); ?>" class="mb-8">
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Nowe hasło</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Potwierdź hasło</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-button font-medium hover:bg-opacity-90 transition">
                Ustaw nowe hasło
            </button>
        </form>
        <?php endif; ?>

        <div class="text-center">
            <p class="text-gray-600">
                Pamiętasz swoje hasło? 
                <a href="login.php" class="text-primary font-medium hover:underline">Zaloguj się</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 