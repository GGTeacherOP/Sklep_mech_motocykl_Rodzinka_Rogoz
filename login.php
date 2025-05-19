<?php
// Strona logowania
$page_title = "Logowanie";
require_once 'includes/config.php';

// Sprawdzenie czy użytkownik jest już zalogowany
if (isLoggedIn()) {
    redirect('account.php');
}

// Obsługa formularza logowania
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? 1 : 0;
    
    // Sprawdzenie czy email i hasło zostały podane
    if (empty($email) || empty($password)) {
        setMessage('Wprowadź email i hasło', 'error');
    } else {
        // Wyszukiwanie użytkownika w bazie danych
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Weryfikacja hasła
            if (password_verify($password, $user['password'])) {
                // Poprawne dane logowania
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Obsługa "zapamiętaj mnie" (w rzeczywistości należałoby użyć cookies)
                if ($remember) {
                    // Tutaj dodać obsługę cookies dla "zapamiętaj mnie"
                }
                
                // Sprawdź rolę użytkownika i przekieruj odpowiednio
                if ($user['role'] === 'admin') {
                    redirect('admin/index.php');  // Przekierowanie do panelu admina
                } else {
                    redirect('index.php');  // Przekierowanie na stronę główną
                }
            } else {
                // Niepoprawne hasło
                setMessage('Niepoprawny email lub hasło', 'error');
            }
        } else {
            // Użytkownik nie istnieje
            setMessage('Niepoprawny email lub hasło', 'error');
        }
    }
}

include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow-sm p-8">
        <h1 class="text-3xl font-bold text-center mb-8">Zaloguj się</h1>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-8">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                <div class="flex justify-end mt-2">
                    <a href="reset-password.php" class="text-sm text-primary hover:underline">Zapomniałeś hasła?</a>
                </div>
            </div>

            <div class="flex items-center mb-6">
                <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-700">Zapamiętaj mnie</label>
            </div>

            <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-button font-medium hover:bg-opacity-90 transition">
                Zaloguj się
            </button>
        </form>

        <div class="relative mb-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500">Lub kontynuuj przez</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-8">
            <button class="flex items-center justify-center bg-blue-600 text-white py-3 rounded-button hover:bg-opacity-90 transition">
                <i class="ri-facebook-fill mr-2"></i>
                Facebook
            </button>
            <button class="flex items-center justify-center bg-red-500 text-white py-3 rounded-button hover:bg-opacity-90 transition">
                <i class="ri-google-fill mr-2"></i>
                Google
            </button>
        </div>

        <div class="text-center">
            <p class="text-gray-600">
                Nie masz konta? 
                <a href="register.php" class="text-primary font-medium hover:underline">Zarejestruj się</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
