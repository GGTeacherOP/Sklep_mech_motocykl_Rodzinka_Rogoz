<?php
// Strona resetowania hasła
$page_title = "Resetowanie hasła";
require_once 'includes/config.php';

// Sprawdzenie czy użytkownik jest już zalogowany
if (isLoggedIn()) {
    redirect('account.php');
}

$message = '';
$message_type = '';

// Obsługa formularza resetowania hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $message = 'Wprowadź adres email';
        $message_type = 'error';
    } else {
        // Sprawdzenie czy użytkownik istnieje
        $sql = "SELECT id, email FROM users WHERE email = '$email'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            // Generowanie tokenu resetowania hasła
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Zapisywanie tokenu w bazie danych
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            
            $update_sql = "UPDATE users SET reset_token = '$token', reset_token_expires = '$expires' WHERE id = $user_id";
            
            if ($conn->query($update_sql)) {
                // Przekierowanie do strony zmiany hasła
                header("Location: new-password.php?token=" . $token);
                exit();
            } else {
                $message = 'Wystąpił błąd podczas generowania tokenu resetowania hasła';
                $message_type = 'error';
            }
        } else {
            $message = 'Nie znaleziono użytkownika o podanym adresie email';
            $message_type = 'error';
        }
    }
}

include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow-sm p-8">
        <h1 class="text-3xl font-bold text-center mb-8">Resetowanie hasła</h1>
        
        <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-8">
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-button font-medium hover:bg-opacity-90 transition">
                Resetuj hasło
            </button>
        </form>

        <div class="text-center">
            <p class="text-gray-600">
                Pamiętasz swoje hasło? 
                <a href="login.php" class="text-primary font-medium hover:underline">Zaloguj się</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?> 