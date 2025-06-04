<?php
// Plik obsługujący wylogowywanie użytkownika
require_once 'includes/config.php';

// Sprawdzenie czy użytkownik jest zalogowany
if (isset($_SESSION['user_id'])) {
    // Zapisanie informacji dla użytkownika
    setMessage('Zostałeś pomyślnie wylogowany.', 'success');
    
    // Usunięcie wszystkich zmiennych sesyjnych
    $_SESSION = array();
    
    // Usunięcie ciasteczka sesji
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Zniszczenie sesji
    session_destroy();
}

// Przekierowanie na stronę główną
header("Location: index.php");
exit();
?>
