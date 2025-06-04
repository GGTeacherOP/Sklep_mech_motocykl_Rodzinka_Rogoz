<?php
// Strona rejestracji
$page_title = "Rejestracja";
require_once 'includes/config.php';

// Sprawdzenie czy użytkownik jest już zalogowany
if (isLoggedIn()) {
    redirect('account.php');
}

// Obsługa formularza rejestracji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['firstName']);
    $last_name = sanitize($_POST['lastName']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $terms = isset($_POST['terms']) ? 1 : 0;
    
    // Walidacja danych
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = "Imię jest wymagane";
    }
    
    if (empty($last_name)) {
        $errors[] = "Nazwisko jest wymagane";
    }
    
    if (empty($email)) {
        $errors[] = "Email jest wymagany";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Podaj poprawny adres email";
    }
    
    if (empty($password)) {
        $errors[] = "Hasło jest wymagane";
    } elseif (strlen($password) < 8) {
        $errors[] = "Hasło musi zawierać co najmniej 8 znaków";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Hasła nie są zgodne";
    }
    
    if (!$terms) {
        $errors[] = "Musisz zaakceptować regulamin i politykę prywatności";
    }
    
    // Sprawdzenie czy email już istnieje
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);
    
    if ($result && $result->num_rows > 0) {
        $errors[] = "Ten adres email jest już zarejestrowany";
    }
    
    // Jeśli nie ma błędów, dodaj użytkownika do bazy danych
    if (empty($errors)) {
        // Szyfrowanie hasła
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Wstawianie użytkownika do bazy danych
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, newsletter) 
                VALUES ('$first_name', '$last_name', '$email', '$phone', '$hashed_password', $newsletter)";
        
        if ($conn->query($sql) === TRUE) {
            // Rejestracja udana, zaloguj użytkownika
            $user_id = $conn->insert_id;
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'user';
            
            setMessage('Rejestracja zakończona pomyślnie', 'success');
            redirect('index.php');
        } else {
            setMessage('Błąd podczas rejestracji: ' . $conn->error, 'error');
        }
    } else {
        // Wyświetl błędy
        $error_message = implode('<br>', $errors);
        setMessage($error_message, 'error');
    }
}

include 'includes/header.php';
?>

<main class="container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow-sm p-8">
        <h1 class="text-3xl font-bold text-center mb-8">Zarejestruj się</h1>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mb-8">
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1">Imię</label>
                    <input type="text" id="firstName" name="firstName" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                </div>
                <div>
                    <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko</label>
                    <input type="text" id="lastName" name="lastName" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                </div>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                <input type="tel" id="phone" name="phone" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło</label>
                <input type="password" id="password" name="password" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                <p class="text-xs text-gray-500 mt-1">Hasło powinno zawierać min. 8 znaków, w tym cyfrę i znak specjalny</p>
            </div>

            <div class="mb-6">
                <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-1">Potwierdź hasło</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div class="flex items-start mb-6">
                <div class="flex items-center h-5">
                    <input type="checkbox" id="terms" name="terms" required
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                </div>
                <label for="terms" class="ml-2 block text-sm text-gray-700">
                    Akceptuję <a href="terms.php" class="text-primary hover:underline">regulamin</a> oraz 
                    <a href="privacy-policy.php" class="text-primary hover:underline">politykę prywatności</a>
                </label>
            </div>

            <div class="flex items-start mb-8">
                <div class="flex items-center h-5">
                    <input type="checkbox" id="newsletter" name="newsletter"
                        class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                        <?php echo isset($_POST['newsletter']) ? 'checked' : ''; ?>>
                </div>
                <label for="newsletter" class="ml-2 block text-sm text-gray-700">
                    Chcę otrzymywać newsletter z informacjami o promocjach i nowych produktach
                </label>
            </div>

            <button type="submit"
                class="w-full bg-primary text-white py-3 rounded-button font-medium hover:bg-opacity-90 transition">
                Zarejestruj się
            </button>
        </form>

        <div class="relative mb-8">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-4 bg-white text-gray-500">Lub zarejestruj się przez</span>
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
                Masz już konto? 
                <a href="login.php" class="text-primary font-medium hover:underline">Zaloguj się</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
